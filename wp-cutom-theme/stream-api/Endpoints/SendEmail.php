<?php

namespace Stream\Endpoints;

use Stream\Support\Authentication;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SendEmail {

  private string $namespace = 'stream/v1';
  private string $route = 'sendEmail';
  private Authentication $authentication;

  public function __construct( Authentication $authentication )
  {
    $this->authentication = $authentication;
  }

  public function registerEndpoints(): void
  {

    register_rest_route(
      $this->namespace,
      '/' . $this->route,
      array(
        array(
          'methods'             => WP_REST_Server::CREATABLE,
          'callback'            => array( $this, 'send' ),
          'permission_callback' => array( $this, 'authentication' ),
          'args'                => array(
            'to'          => array(
              'description'       => 'Array emails of recipients.',
              'type'              => 'array',
              'required'          => true,
              'validate_callback' => array( $this, 'validateEmailArray' ),
            ),
            'subject'     => array(
              'description'       => 'Subject email.',
              'type'              => 'string',
              'required'          => true,
              'validate_callback' => array( $this, 'validateString' ),
              'sanitize_callback' => array( $this, 'sanitizeString' ),
            ),
            'body'        => array(
              'description'       => 'Body email.',
              'type'              => 'string',
              'required'          => true,
              'validate_callback' => array( $this, 'validateString' ),
              'sanitize_callback' => array( $this, 'sanitizeString' ),
            ),
            'attachments' => array(
              'description'       => 'Array URLs of filenames',
              'type'              => 'array',
              'required'          => false,
              'validate_callback' => array( $this, 'validateUrlAttachments' ),
              'sanitize_callback' => array( $this, 'sanitizeUrlArray' ),
            ),
          ),
        ),
      ),
    );

  }

  public function sanitizeUrlArray( $value, $request, $key )
  {
    $urls = array();

    if ( ! $value) {
        return $urls;
    }

    foreach ( $value as $url ) {
      $urls[] = sanitize_url( $url );
    }

    return $urls;
  }

  public function validateUrlAttachments( $value, $request, $key )
  {
    if ( ! is_array( $value ) || empty( $value ) ) {
      return new WP_Error( 'invalid_array', "Param '{$key}' - is not an array or empty." );
    }

    foreach ( $value as $url ) {
      $url = strtolower( $url );
      if ( esc_url( $url ) !== $url ) {
        return new WP_Error( 'invalid_url', "Invalid URL of attachment: '{$url}'" );
      }

      $parsed_url = parse_url( $url );
      $host = $parsed_url[ 'host' ] ?? false;
      $url_path = $parsed_url[ 'path' ] ?? false;

      $site_url = parse_url( get_home_url() );

      $slugs = $url_path ? explode( '/', $url_path ) : [];

      if ( ! $host || $host !== $site_url[ 'host' ] || ! in_array( UploadAttachment::getNameDir(), $slugs ) ) {
        return new WP_Error( 'unknown_path', "Unknown path to attachment: '{$url}'" );
      }
    }

    return true;
  }

  public function validateString( $value, $request, $key )
  {
    if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
      return new WP_Error( 'invalid_string', "Param '{$key}' - is not a string or empty." );
    }

    return true;
  }

  public function validateEmailArray( $value, $request, $key )
  {
    if ( ! is_array( $value ) || empty( $value ) ) {
      return new WP_Error( 'invalid_array', "Param '{$key}' - is not an array or empty." );
    }

    foreach ( $value as $email ) {
      if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', "Invalid email '{$email}'" );
      }
    }

    return true;
  }

  public function sanitizeString( $value, $request, $key )
  {
    return sanitize_text_field( $value );
  }

  public function send( WP_REST_Request $request ): WP_REST_Response
  {
    $to = $request->get_param( 'to' );
    $subject = $request->get_param( 'subject' );
    $body = html_entity_decode( $request->get_param( 'body' ) );
    $attachments = $request->get_param( 'attachments' );

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // Join attachment urls to body email
    $body = $attachments
      ? $body . '<br><br>' . 'Attachments:' . '<br><br>' . implode( '<br><br>', $attachments )
      : $body;

    if ( wp_mail( $to, $subject, $body, $headers ) ) {
      $response = $this->prepareResponse( 'Email was sent successfully.' );
      return new WP_REST_Response( $response, 200 );
    } else {
      $response = $this->prepareResponse( 'Email was not sent. Something went wrong.' );
      return new WP_REST_Response( $response, 500 );
    }
  }

  private function prepareResponse( string $message ): array
  {
    return array( 'message' => $message );
  }

  public function authentication( WP_REST_Request $request ): bool
  {
    return $this->authentication->checkBasicAuth( $request );
  }

}

<?php

namespace Stream\Endpoints;

use Stream\Support\UploadException;
use Stream\Support\Authentication;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class UploadAttachment {

  private static string $nameDir = 'stream-api';
  private string $namespace = 'stream/v1';
  private string $route = 'uploadAttachment';
  private string $uploadDir;
  private string $uriDir;
  private array $response;
  private UploadException $uploadException;
  private Authentication $authentication;

  public function __construct( UploadException $uploadException, Authentication $authentication )
  {
    $this->uploadException = $uploadException;

    $this->authentication = $authentication;

    $this->uploadDir = $this->createUploadDir( self::$nameDir );

    $this->uriDir = $this->createUriDir( self::$nameDir );

    $this->prepareResponse();
  }

  private function createUploadDir( string $nameDir ): string
  {
    $upload_dir = wp_upload_dir();
    $year = date( 'Y' );
    $month = date( 'm' );

    $dir = "{$upload_dir[ 'basedir' ]}/$nameDir/$year/$month/";

    if ( ! file_exists( $dir ) ) {

      wp_mkdir_p( $dir );

    }

    return $dir;
  }

  private function createUriDir( string $nameDir ): string
  {
    $upload_dir = wp_upload_dir();
    $year = date( 'Y' );
    $month = date( 'm' );

    return "{$upload_dir[ 'baseurl' ]}/$nameDir/$year/$month/";
  }

  private function prepareResponse( string $errorMessage = '', string $fileUri = '' ): void
  {
    $this->response[ 'errorMessage' ] = $errorMessage;
    $this->response[ 'fileUri' ] = $fileUri;
  }

  public static function getNameDir()
  {
    return self::$nameDir;
  }

  public function registerEndpoints(): void
  {
    register_rest_route(
      $this->namespace,
      '/' . $this->route,
      array(
        array(
          'methods'             => WP_REST_Server::CREATABLE,
          'callback'            => array( $this, 'upload' ),
          'permission_callback' => array( $this, 'authentication' ),
        ),
      )
    );
  }

  public function upload( WP_REST_Request $request ): WP_REST_Response
  {
    $fileParams = $request->get_file_params();

    $file = ! empty( $fileParams ) ? array_pop( $fileParams ) : array();

    $validation = $this->validateFile( $file );
    if ( $validation instanceof WP_REST_Response ) {
      return $validation;
    }

    $fileName = $this->createSanitizedUniqFileName( $file[ 'name' ] );

    if ( move_uploaded_file( $file[ 'tmp_name' ], $this->uploadDir . $fileName ) ) {

      $this->prepareResponse( '', $this->uriDir . $fileName );
      return new WP_REST_Response( $this->response, 200 );

    } else {

      $errorUpload = 'Uploading process. File not a valid or cannot be saved for some reason.';
      error_log( "STREAM-API: {$errorUpload}" );
      $this->prepareResponse( $errorUpload );
      return new WP_REST_Response( $this->response, 500 );

    }

  }

  /**
   * @param array $file Expecting a file as a super global php variable $_FILES
   *
   * @return bool|\WP_REST_Response True or WP_REST_Response with status code 400
   */
  private function validateFile( array $file )
  {
    if ( ! isset( $file[ 'error' ] ) ) {
      $this->prepareResponse( 'File missed in request.' );
      return new WP_REST_Response( $this->response, 400 );
    }

    if ( is_array( $file[ 'error' ] ) ) {
      $this->prepareResponse( 'Array of files is forbidden.' );
      return new WP_REST_Response( $this->response, 400 );
    }

    if ( $file[ 'error' ] !== UPLOAD_ERR_OK ) {
      $errorMessage = $this->uploadException->codeToMessage( $file[ 'error' ] );
      $statusCode = $this->uploadException->codeToStatus( $file[ 'error' ] );
      $this->prepareResponse( $errorMessage );
      return new WP_REST_Response( $this->response, $statusCode );
    }

    return true;
  }

  public function createSanitizedUniqFileName( string $fileName ): string
  {
    // Remove potentially dangerous characters
    $fileName = preg_replace( '/[^\w\s\-.]/', '', $fileName );
    $fileName = str_replace( ' ', '_', $fileName );
    $fileName = substr( $fileName, 0, 200 );
    $timestamp = date( 'd_H.i.s' );
    $fileInfo = pathinfo( $fileName );
    $uniqId = substr( uniqid(), -6 );
    return "{$timestamp}_{$uniqId}_{$fileInfo[ 'filename' ]}.{$fileInfo[ 'extension' ]}";
  }

  public function authentication( WP_REST_Request $request ): bool
  {
    return $this->authentication->checkBasicAuth( $request );
  }

}

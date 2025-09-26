<?php

namespace Stream\Support;

use WP_REST_Request;

class Authentication {

    /**
     * Check Basic Authentication with WP Application Passwords Secret
     *
     * @see https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#basic-authentication-with-application-passwords
     * @see https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
     * @see https://everything.curl.dev/http/auth
     *
     * @param  WP_REST_Request  $request
     *
     * @return bool
     */
  public function checkBasicAuth( WP_REST_Request $request ): bool
  {
    $base64Encoded = $request->get_header( 'Authorization' );

    if ( is_null( $base64Encoded ) ) {
      return false;
    }

    [ $authType, $credentials ] = explode( ' ', $base64Encoded, 2 );

    if ( strcasecmp( $authType, 'Basic' ) !== 0 ) {
      return false;
    }

    $decodedCredentials = base64_decode( $credentials );

    [ $username, $password ] = explode( ':', $decodedCredentials, 2 );

    /* 1st param 'input_user' set to null and is typically used when multiple authentication methods are involved,
    and a callback is performed before application password authentication. In such cases, the 'input_user'
    parameter carries the result of the previous authentication attempt. */
    $user = wp_authenticate_application_password( null, $username, $password );

    if ( is_a( $user, 'WP_User' ) ) {
      return true;
    }

    return false;
  }

}

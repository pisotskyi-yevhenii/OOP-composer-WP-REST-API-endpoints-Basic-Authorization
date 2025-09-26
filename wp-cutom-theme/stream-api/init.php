<?php

add_action( 'rest_api_init', function () {

  $uploadException = new Stream\Support\UploadException();

  $authentication = new Stream\Support\Authentication();

  ( new Stream\Endpoints\UploadAttachment( $uploadException, $authentication ) )->registerEndpoints();

  ( new Stream\Endpoints\SendEmail( $authentication ) )->registerEndpoints();

} );

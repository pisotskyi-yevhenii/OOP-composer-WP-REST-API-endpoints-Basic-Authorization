<?php

namespace Stream\Support;

class UploadException {

  /**
   * Get error message based on php file error code on upload
   *
   * @see https://www.php.net/manual/en/features.file-upload.errors.php
   *
   * @param int $code php file upload error code
   *
   * @return string Error message
   */
  public function codeToMessage( int $code ): string
  {
    switch ( $code ) {
      case UPLOAD_ERR_INI_SIZE:
        return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';

      case UPLOAD_ERR_FORM_SIZE:
        return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';

      case UPLOAD_ERR_PARTIAL:
        return 'The uploaded file was only partially uploaded.';

      case UPLOAD_ERR_NO_FILE:
        return 'No file was uploaded.';

      case UPLOAD_ERR_NO_TMP_DIR:
        return 'Missing a temporary folder. Needs checking PHP configuration.';

      case UPLOAD_ERR_CANT_WRITE:
        return 'Failed to write file to disk. Needs checking folder permissions.';

      case UPLOAD_ERR_EXTENSION:
        return 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.';

      default:
        return 'Unknown upload error on server.';
    }
  }

  /**
   * Get rest response status code based on php file error code on upload
   *
   * @see https://www.php.net/manual/en/features.file-upload.errors.php
   *
   * @param int $code php file upload error code
   *
   * @return int Rest response status code
   */
  public function codeToStatus( int $code ): int
  {
    switch ( $code ) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
      case UPLOAD_ERR_PARTIAL:
      case UPLOAD_ERR_NO_FILE:
        return 400;

      case UPLOAD_ERR_NO_TMP_DIR:
      case UPLOAD_ERR_CANT_WRITE:
      case UPLOAD_ERR_EXTENSION:
      default:
        return 500;
    }
  }

}

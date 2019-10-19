# Laravel Blog
Blog based on laravel 6.2 framework.

## 1. User authentication
  * Add composer dependency: composer require laravel/ui
  * Generate UI view: ./artisan ui vue --auth
  * Install node.js dependency：npm install
  * Compile vue：npm run dev
  * Create database table：./artisan migrate:refresh
  * **Forgot Your Password? ** Error in page sending email
      * local.ERROR: Expected response code 250 but got code "553", with message "553 Mail from must equal authorized user
      * **Solve:** Add MAIL_FROM_ADDRESS and MAIL_FROM_NAME to the configuration file ".env", and MAIL_FROM_ADDRESS is equal to MAIL_USERNAME.

## 2. 


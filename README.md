## Installation Process

    1. Clone the repository.
    2. Run composer install console command.
    3. Create the .env file using the console command "cp .env.example .env".
    4. Create a database with your choice.
    5. Fillup the .env file database requirements with your local settings.
    6. Generate key using "php artisan key:generate".
    7. Import our given SQL file into your database (you will find our SQL file in base directory).
    8. Migrate the new tables using "php artisan migrate".
    9. Run npm install console command.
    10. Serve the project by "php artisan serve" & "npm run watch"

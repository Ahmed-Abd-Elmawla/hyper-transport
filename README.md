<p align="center"><img src="public\assets\images\logo.webp" width="400" alt="Hyper Transport Logo"></p> 


## Installation
After downloading the project files to your machine you need to follow this instructions:

1. Create `.env` file and copy the content of `.env.example` to it

```bash
cp .env.example .env
```

2. Open `.env` file and edit the following configuration based on the configuration for your machine

```php
APP_URL=

DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```
> [!CAUTION]
> Make sure you have entered the configuration for your machine correctly before continuing

3. After create the database you can install this custom command to complete the installation

```bash
php artisan hyper-transport:install
```

Once finished a link will appear in terminal `CTRL + Click it` to open in your browser

> [!TIP]
> You can login to dashboard by using the following credentials:
>
> Email
> ```bash
> admin@admin.com
> ```
> Password
> ```bash
> admin123
> ```

## Approach and thought process

I assumed that the trip would have a specific start and end date-time value for each trip, from Filament admin dashboard you can:

- Manage companies, drivers, and vehicles
- Schedule and track trips
- View availability reports
- Monitor system statistics

__Workflows__

1. `Company Setup` Create companies and assign drivers/vehicles
2. `Driver Management` Add drivers with licensing and status information
3. `Vehicle Fleet` Register vehicles with capacity and status information
4. `Trip Planning` Schedule trips with automatic conflict detection
5. `Availability Checking` Use the availability lookup to find free resources

__Status Management__
The system implements intelligent status-based restrictions:

- `Drivers` Cannot edit details when status is "inactive"
- `Vehicles` Cannot edit details when status is "in_use"
- `Trips` Cannot edit any field when status is "in_progress"

__Components__

- `Models` Company, Driver, Vehicle, Trip with proper relationships
- `Services` AvailabilityService for conflict detection and resource lookup
- `Jobs` StartTripJob, EndTripJob for automated trip management
- `Observers` TripObserver for job lifecycle management
- `Resources` Filament resources with custom form logic and validation

## Testing

The application includes testing with Pest PHP:

```bash
# Run all tests
php artisan test
```
> [!CAUTION]
> You should run the following command after running the tests

```bash
php artisan migrate --seed
```
## Future updates

- Add roles and permissions for users
- Add notifications system between users
- Develop Advanced analytics and reporting
- Enhance the user interface with more interactive features
- Enhance the trip scheduling process with more options
- Enhance the jobs with more features like:
  - Add more validation rules to the trip form
  - Add more logic to the trip form

**Built with ❤️ using Laravel 11 & Filament 3**

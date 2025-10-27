# php-tb-web
## Directory Structure
```text
/ src
    ├── app
    │   ├── ...
    │   ├── Enums                                           # Enum class directory
    │   │   └── Status.php                                  # Sample enum
    │   ├── Helpers                                         # Helper class directory
    │   │   └── LogHelperService.php                        # Custom logger (already binding in AppServiceProvider)
    │   ├── Http
    │   │   ├── Controllers
    │   │   │   ├── Controller.php                          # Base controller
    │   │   │   ├── Api                                     # API controller directory
    │   │   │   │   ├── BaseController.php                  # API base controller (interface)
    │   │   │   │   ├── ProgramController.php               # Sample controller
    │   │   │   │   └── ...
    │   │   │   └── ...
    │   │   ├── Middleware                                  # Middleware classes
    │   │   ├── Requests                                    # Form request validation class
    │   │   │   ├── BaseRequest.php
    │   │   │   ├── ProgramRequest.php                      # Sample form request validation
    │   │   │   └── ...
    │   │   ├── Resources                                   # Resource classes
    │   │   │   ├── ProgramResource.php                     # Sample resource
    │   │   │   └── ...
    │   │   ├── Models                                      # Middleware classes
    │   │   │   ├── BaseModel.php                           # Base model
    │   │   │   ├── Program.php                             # Sample model
    │   │   │   └── ...
    │   │   ├── Providers                                   # Service providers
    │   │   │   ├── ServiceRepositoryServiceProvider.php    # Service-Repository binding service provider
    │   │   │   └── ...
    │   │   ├── Repositories                                # Repositories classes
    │   │   │   ├── Interface                               # Repositories interfaces
    │   │   │   ├── BaseRepository.php                      # Base repository
    │   │   │   ├── ProgramRepository.php                   # Sample repository
    │   │   │   └── ...
    │   │   └── Services                                    # Repositories classes
    │   │   │   ├── Interface                               # Service interfaces
    │   │   │   ├── BaseService.php                         # Base service
    │   │   │   ├── ProgramService.php                      # Sample service
    │   │   │   └── ...
    ├── bootstrap
    │   ├── app.php                             # Application bootstrapping
    ├── config                                  # Configuration files
    ├── database
    │   ├── factories                           # Model factories
    │   ├── migrations                          # Database migrations
    │   │   ├── xxx_create_programs_table.php   # Sample table migration
    │   │   └── ...
    │   ├── seeds                               # Database seeds
    │   │   ├── csv                             # CSV data seeder
    │   │   │   └── program.csv                 # Sample CSV data seeder
    │   │   ├── BaseImportCSVSeeder.php
    │   │   └── ProgramSeeder.php               # Sample CSV seeder
    ├── public                                  # Publicly accessible files
    ├── resources
    │   ├── lang                                # Language files
    │   ├── views                               # Blade templates
    │   ├── assets
    │   │   ├── css                             # Stylesheets
    │   │   ├── js                              # JavaScript files
    │   ├── ...
    ├── routes
    │   ├── web.php                             # Web routes
    │   ├── api.php                             # API routes
    ├── storage                                 # Temporary storage files
    ├── tests                                   # PHPUnit test cases
    ├── vendor                                  # Composer dependencies
    ├── .env                                    # Environment configuration
    ├── .env.example                            # Example environment configuration
    ├── .gitignore                              # Git ignore file
    ├── composer.json                           # Composer dependencies
    ├── README.md                               # Project readme

```

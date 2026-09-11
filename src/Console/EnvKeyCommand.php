<?php

namespace Starlight93\LaravelSmartApi\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EnvKeyCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'project:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the JWTAuth secret key used to sign the tokens';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ( !file_exists($path = $this->envPath()) ) {
            return $this->warning(".env file does not exist");
        }
        $data = [
            "EDITOR_PASSWORD=12345",
            "EDITOR_FRONTENDERS=",
            "EDITOR_BACKENDERS=",
            "EDITOR_OWNERS=dev-owner",
            "LOG_SENDER=",
            "LOG_PATH=".uniqid(),
            "CLIENT_CHANNEL=",
            "API_ROUTE_PREFIX=api",
            "API_USER_TABLE=default_users",
            "API_PROVIDER=",

            "# jwt | sanctum | passport\nAPI_AUTH_DRIVER=jwt",
            "# kosong = auto (jwt/passport -> api, sanctum -> sanctum)\nAPI_AUTH_GUARD=",
            "# kosong = override auth config hanya saat driver jwt\nAPI_AUTH_OVERRIDE_CONFIG=",
            "API_AUTH_TOKEN_NAME=api",
            "JWT_TTL=43800",
            "AUTOCREATE_MIGRATION=true",

            "MAIL_HOST=smtp.googlemail.com",
            "MAIL_PORT=465",
            "MAIL_PASSWORD=",
            "MAIL_USERNAME=",
            "MAIL_ENCRYPTION=ssl",
            "MAIL_FROM_ADDRESS=",
            "MAIL_FROM_NAME=",
        ];

        $content = file_get_contents($path);
        foreach($data as $keyVal){
            // Entri boleh multiline (baris '#' komentar + baris key=value di akhir).
            $lines = explode("\n", $keyVal);
            $key   = explode('=', end($lines))[0];
            if ( !Str::contains($content, $key) ) {
                file_put_contents($path, PHP_EOL.$keyVal, FILE_APPEND);
            }
        }
    }

    /**
     * Get the .env file path.
     *
     * @return string
     */
    protected function envPath()
    {
        if (method_exists($this->laravel, 'environmentFilePath')) {
            return $this->laravel->environmentFilePath();
        }

        // check if laravel version Less than 5.4.17
        if (version_compare($this->laravel->version(), '5.4.17', '<')) {
            return $this->laravel->basePath().DIRECTORY_SEPARATOR.'.env';
        }

        return $this->laravel->basePath('.env');
    }
}

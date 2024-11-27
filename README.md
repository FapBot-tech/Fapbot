# ChatBot

## initial setup
First you'd need a server to run this on, there's a few requirements
- Apache / NGINX
- PHP8 or higher
- PHP curl extension
- MySQL or PostgreSQL database
- Composer
- NPM and yarn installed

Once you have all this you can download the git files and put them on your server, make sure the webserver loads the index in the /public folder.

Next up `composer install` should be run to make sure all the needed packages are installed. Followed by a `yarn install` and `yarn build` to make sure all the JS and CSS packages are installed and compiled. 

You should also make an `.env` file with the following variable, values should be changed to be correct
```dotenv 
APP_ENV=prod
APP_SECRET=31n2nudfwaiudwwad
TRUSTED_HOSTS='^(localhost|your\.domain\.com)$'

# Format described at https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
# For an SQLite database, use: "sqlite:///%kernel.project_dir%/var/data.db"
# For a PostgreSQL database, use: "postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=11&charset=utf8"
DATABASE_URL=mysql://mutes:xxxxxxxx@127.0.0.1:3306/mutes?serverVersion=5.7

MAILER_DSN=smtp://noreply%40fapbot.tech:xxxxx@127.0.0.1:587?verify_peer=0
```

## Setting up the MatterMost server
Create a file in the '/config/packages' folder called `mattermost.yaml` with the following content
```yaml
parameters:
  mattermost.token_id: "xxxxx"
  mattermost.access_token: "xxxx"
  mattermost.base_url: 'https://xxxx/api/v4'
```
Make sure to verify the settings in `packages/mattermost.yaml`

## Setting up the database
At this point the code part is all setup as it should be, but it's still missing its database, to set this up run the command `php bin/console doc:mig:mig` from whatever directory these files are in. 

Now the database it set up as it should be, but it's still completely empty so no users can log in just yet. To create the first user we'll have to manually add it to the database. Open the database and go to the users table, here you can add your Super admin user first, 
and all channels get assigned to this user. The username can be whatever you want it to be, the roles should be `["ROLE_SUPER_ADMIN", "ROLE_ADMIN", "ROLE_USER"]` 
and last but not least the password can be generated by running `php bin/console security:hash-password`. 

## Setting up the channels
You can now login to the application, under the menu item Admin area there should be a item called channels, here you can add all the channels you want the bot to have access too. Make sure the bot is in the channel, otherwise it can't do any commands. The Channel ID can be found from the administration area in RocketChat.
Be careful while adding these, because especially when it's a long list it's easy to make mistakes. I'd recommend using the [channel list api call in rocketchat ](https://developer.rocket.chat/reference/api/rest-api/endpoints/rooms/channels-endpoints/list)https://developer.rocket.chat/reference/api/rest-api/endpoints/rooms/channels-endpoints/list

# prerequisites

* PHP 5.5.0
* 'allow_url_fopen' in your php.ini or php-curl extension

# Prelude configuration

## Prelude API

### Add api authorisation to your prelude's pgsql db

1- connect to postgres prewikka db

   ```bash
   psql -U postgres -W prewikka
   ```

2- Insert authorizations

Currently, prelude application doesn't have any graphical page to insert your authorization for an external application, so we need to manually insert it

   ```sql
   INSERT INTO prewikka_oauth_client
      (ownerid, client_id, client_secret, redirect_uri, application_name, description)
   VALUES
      ('21232f297a57a5a743894a0e4a801fc3', 'client_glpi', 'supersecret', 'http://path/to/glpi/plugin/prelude/front/config.form.php?connect_api=true', 'glpi', 'glpi oauth');
   ```

You may need to replace some values in the previous sql query. Here is descriptions for the differents fields:
- owner_id: prelude user, you should find the value in prewikka_user.userid field.
- client_id: key name of your application, you need to set up this  key in the configuration of prelude plugin.
- client_secret: password for your application, you need to set upm this value in the glpi plugin configuration.
- redirect uri: at the first configuration of the glpi prelude plugin, you will be redirected to prelude application and asked for login/authorize api acces. After this step, the redirect_uri will be called for returning into glpi.
- application_name: name of the authorized application.
- description: description of the authorized application.

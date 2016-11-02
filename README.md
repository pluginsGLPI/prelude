# prerequisites

* PHP 5.5.0
* 'allow_url_fopen' in your php.ini or php-curl extension

# Prelude configuration

## Prelude API

### Add api authorisation to your prelude's pgsql db

1- connect to postgres

   ```bash
   psql -U postgres -W
   ```

2- Change current database
   ```
   \connect prewikka
   ```

3- Insert authorizations

   ```sql
   INSERT INTO prewikka_oauth_client
      (ownerid, client_id, client_secret, redirect_uri, application_name, description)
   VALUES
      ('21232f297a57a5a743894a0e4a801fc3', 'client_glpi', 'supersecret', 'a', 'glpi', 'glpi oauth');
   INSERT INTO prewikka_oauth_access_tokens
      (token, refresh_token, client_id, userid, expires, scopes)
   VALUES
      ('my_token', 'my_token', 'client_glpi', '21232f297a57a5a743894a0e4a801fc3', '01/01/2020', 'IDMEF_VIEW');
   ```

### Test connection

```bash
curl --insecure -H "Authorization: Bearer my_token" \
'http://path/to/prelude/api?action=retrieve&request=%7B%22path%22%3A%5B%22log.timestamp%22%2C%22log.host%22%5D%7D'
```
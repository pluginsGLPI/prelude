# Prelude plugin for glpi

This plugin integrates [prelude SIEM](https://www.prelude-siem.com/) with GLPI.


## Features

* Override Ticket-Assets association by adding a _type of link_ dropdown.
* Add a _Prelude_ tab in tickets form to see associated IDMEF alerts
* Integrate also with GLPI [OpenVAS plugin](https://github.com/pluginsGLPI/openvas). When this plugin is present, an openvas status will be added in Ticket' items list.


## Installation

### Prerequisites

You need the followings components:
- GLPI (at least 9.1 version)
- Prelude Siem (at least 3.1 version)
- PHP >= 5.5
- php-curl extension or `allow_url_fopen` in your php.ini

### Prelude configuration

We need an oauth client configuration in prelude to connect our plugin.
At the moment, prelude doesn't provide any tool to add clients, so we need to add data directly in the postgres database:

1. Connect to postgres in prelude OVA (default password is 'postgres')

```bash
  psql -U postgres -W prewikka
```

2. Insert client authorization

```sql
INSERT INTO prewikka_oauth_client
      (ownerid, client_id, client_secret, redirect_uri, application_name, description)
   VALUES
      ('21232f297a57a5a743894a0e4a801fc3', 'client_glpi', 'supersecret', 'http://path/to/glpi/plugin/prelude/front/config.form.php?connect_api=true', 'glpi', 'glpi oauth');
```

You may need to replace some values in the previous sql query. Here is descriptions for the differents fields:

* **owner_id**: prelude user, you should find the value in the `userid` field of the `prewikka_user` table.
* **client_id**: key name of your application, you need to set up this key in the configuration of prelude plugin.
* **client_secret**: password for your application, you need to set up this value in the glpi plugin configuration.
* **redirect uri**: at the first configuration of the glpi prelude plugin, you will be redirected to prelude application and asked for login/authorize api acces. After this step, the redirect_uri will be called for returning into glpi.
* **application_name**: name of the authorized application.
* **description**: description of the authorized application.

### Glpi Configuration

Copy the plugin folder into your glpi/plugins directory.
The folder must be named 'prelude' otherwise GLPI framework will fail to initialize the plugin.

Next, go to glpi interface, navigate to the _Administatrion > Plugins_ page.
Find the prelude line, click on _Install_ button, and after refresh, click on _Enable_ button. 

Once the plugin enabled, you may click on this name or go into _Setup > General_ menu to display the plugin configuration.

You will see this page:

![Prelude plugin empty configuration](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/config_empty.png)

Fill the fields like set in [Prelude configuration paragraph](#prelude-configuration) and click on _Save_ button.
You will see a new block after the button describing the Prelude's API status. 

![Prelude plugin not connected api](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/config_connect.png)

To finish, you just need to click on _Connect to prelude API_ to be redirected to the authorization page:

![Oauth authorization window](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/prelude_oauth.png)

After this last step, all lines of _Api Status_ should be green and your plugin is successsfully installed and configurated.


## Usage

### Items-Tickets association

In ticket form, you have access to an enhanced version of items tab.
A new dropdown (and column) to set the _type of link_ between and an asset and ticket.

![Items Ticket association ](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/feature_itemticket.png)

### IDMEF alerts in tickets

In ticket form, a _prelude_ tab is added and displays:
- A button to transform the ticket in problem
- A button to add a group of alerts (_plus_ icon).

A click of this display a mini form to import a group of alerts from prelude API:

![Import alerts form ](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/feature_addalerts.png)

You need to set the following fields:
* **Name**: a label describing the current group of alerts.
* **URL**: an url to provide a redirection to prelude (in order to see alerts in the prelude context)
* **Criteria**: a set of [prelude IDMEF criteria](https://www.prelude-siem.org/projects/prelude/wiki/IDMEFCriteria) sent to the API to filter the alerts collection.

After submit, you'll see a listing of the alerts.

![IDMEF Alerts ](https://raw.githubusercontent.com/pluginsGLPI/prelude/develop/screenshots/feature_alerts.png)


## GLPI API

The Prelude instance should drive the ticket addition/update etc.
From their interface, you'll have buttons to do theses actions.

If you still need to control data with our [Rest API](https://github.com/glpi-project/glpi/blob/master/apirest.md), here are some examples.

In each provided queries in example, you need to replace some parameters (`session_token` and `glpi http path`).

### Items-Tickets association

* Add

```
curl -X POST \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
-d '{"input": [{"itemtype": "Computer", "items_id": 1, "tickets_id": 25}]}' \
'http://path/to/glpi/apirest.php/PluginPreludeItem_Ticket'

< 201 OK
< {"id": 1}
```

* Update

```
curl -X PUT \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
-d '{"input": [{"tickets_id": 20}]}' \
'http://path/to/glpi/apirest.php/PluginPreludeItem_Ticket/1'

< 200 OK
< [{"1":true, "message": ""}]
```

* Delete

```
curl -X DELETE \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
'http://path/to/glpi/apirest.php/PluginPreludeItem_Ticket/1'

< 200 OK
< [{"1":true, "message": ""}]
```

### IDMEF alerts in tickets

* Add a group of alerts

```
curl -X POST \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
-d $'{"input": [{"name": "group of alerts", "tickets_id": 1, "url": "http://path/to/prelude?with_parameters", "params_api": "{\\\"criteria\\\":[\\\"alert.create_time > \'2016-11-01\'\\\"]}"}]}' \
'http://path/to/glpi/apirest.php/PluginPreludeTicket'

< 201 OK
< {"id": 15}
```

* Update an existing group of alerts

```
curl -X PUT \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
-d '{"input": {"name": "title renamed"}}' \
'http://path/to/glpi/apirest.php/PluginPreludeTicket/15'

< 200 OK
[{"15":true, "message": ""}]
```

* Delete an existing group of alerts

```
curl -X DELETE \
-H 'session-token: ##my_session_token##' \
-H 'Content-Type: application/json' \
'http://path/to/glpi/apirest.php/PluginPreludeTicket/15'

< 200 OK
[{"15":true, "message": ""}]
```
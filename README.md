EDD Remote Installer
============

Remotely install plugins and themes. The package consists of a plugin that has to be installed on a server running EDD (and optionally EDD-SL) and a demo plugin for the client sites.

## Usage

### Server-side

Server-side you have to install and activate the plugin.

This will expose 3 new actions to EDD:
`check_download` checks if a download is free or not.
`get_download` gets the file of the download
`get_downloads` gets a json array of all the available products.

After you install the plugin, You will have to go to its admin page (under settings) and select a download category for your plugins and another one for your themes.

This will allow you to limit the products that will be available for remote installation using categories.

### Client-side

This is a minimal example of how to use the plugin client-side. This is the plugin you will be distributing with your products and it only requires you to change 1 line if you intend to keep it the way it is:

```php
new EDD_RI_Client( 'http://press.codes' );
```
This will use the `get_downloads` action from the server to get a list of all our available projects and you can change the URL to the one that you use on your own store.

It then creates a new options page where the user is able to install their products.
If a product is free then it is directly downloaded and installed.
If it's a billable product, then a popup text form is displayed and the user has to enter their license key in order to procees.
Licenses are created using the [EDD Software Licensing](https://easydigitaldownloads.com/extensions/software-licensing/) plugin.

## A Word of CAUTION

This plugin is still at an early stage in its development and needs a lot of things to be fixed. This is for the time being a proof of concept and an invitation to collaborate and build a kick-ass installer for our EDD-based stores.

Themes installation currently does not work, but plugins are fully operational.

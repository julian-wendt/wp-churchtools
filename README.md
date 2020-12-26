# ChurchTools Plugin for WordPress

A plugin to utilize ChurchTools resources on a WordPress Website. At the moment, the plugin is able to regularly synchronize events from the ChurchTools calendar into the WordPress Database using the ChurchTools API from where they can be shown on a Website. Maybe, more will come in the future.

## Installation

To install the plugin, simply upload all files in this project into `wp-content/plugins/churchtools`. Rename the `settings-sample.json` into `settings.json` and insert the corresponding parameters:

* `tenantName` is the name of your ChurchTools instance. In your ChurchTools url this is the part before church.tools, e.g. https://instance.church.tools.
* `accessToken` is required if more calendars than the public one must be synchronized. We know, this is not a secure way to store tokens so feel free to find a more secure solution.
* `ignoreCert` can help in local development scenarios if a certificate is not present, or the environment is not willing to trust the existing one ;-)
* `eventCategoryIds` must contain at least one ID of a calendar to synchronize. You can find the ID if you log in to ChurchTools, go to Calendar and click on the three dots next to a calendar in the overview on the left side. In the right upper corner of the opening window should be a number prefixed with a `#`.
* `eventLookupFromDay` defines the first day from which events are beeing synchronized. With a `0` the synchronization starts with all current and future events from today. With a `1`, today's events will be skipped, instead all future events will be synchronized from tomorrow.
* `eventLookupToDay` defines from how many days into the future events should be synchronized.

## How to synchronize events

During the plugin activation, a WP Cron Job is beeing created to run regularly every quarter-hour. Furthermore, a first initial import is exeuted right at the activation.

## How to get calendar events

Use `ChurchTools\ChurchEvents\Event::query()` to return an array of event objects from the database. Use `ChurchTools\ChurchEvents\Event::query()` to return an array of event objects from the database. The method requires to parameters:
* `startDate` must be today or a future date. Format: `Y-m-d`
* `endDate` is allowed beeing equal to `startDate`. Format: `Y-m-d`

To access the event properties, use the following methods:
* `id()` returns the database event ID.
* `title()` returns the event title.
* `startdate()` returns the events start date and time.
* `enddate()` returns the events end date and time.
* `link()` returns the link provided in the appropriate field in ChurchTools.
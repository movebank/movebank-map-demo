# Movebank Map Demo

Movebank offers sample code that can be used to pull data from Movebank and display it on external websites using Google Maps. Here are some details about how it works:

To work, your website must allow you to use JavaScript code. Some website providers, such as wordpress.com, do not allow this.

There are two options for the code:
Option 1. HTML: Here the entire code can be posted on an html page. For this to work, the permissions of your study in Movebank must be set so that they can be downloaded by the public, and the "Prompt users to accept license terms" option must be unchecked. This is because when someone opens your website to view the map, Movebank has to send the data to your website, which is essentially the same as the website downloading your data. Including a username and password in the code to access protected data would pass the username and password over the internet with no security.

Option 2: PHP: The code is very similar to the first option, but includes PHP code that can be stored on your local server, which lets you access the data with your Movebank credentials securely and display them on the page. With this option you can show a map of data that are not public in Movebank.

-----------------------------------------------------------
## Details about the sample files

A few notes about using the sample code:

- Every time someone loads your page, your site will request data from Movebank according to your specifications. Changes to your animals or permissions in Movebank can cause these specifications to become out of date. For example, if new animals are added or if an animal’s name is changed in Movebank, you will need to update this code accordingly so your map will continue to load.

- To set up your map, search the text in the code for !! to find comments for the lines that need to be modified to display your map. 

At minimum, you will need to

PHP version
- Update the study_id and individual_local_identifiers in the URL (line 4) for the study and animals you want to show on the map.

- Update the username/password (lines 15-16) with the Movebank credentials for a user with permission to download the data from the study. The credentials there now are for a temporary test user.

HTML version
- Update the study_id (line 7) and individual_local_identifiers (line 8) for the study and animals you want to show in the map.

Also note that

- Use of characters other than $-_.+!*'(), in the URL, for example for animal names, or for passwords, may cause problems.

- Spaces in animal names should be replaced with “%20” in the URL.

- The number of colors listed under “var colors” should match the number of animals.

We highly recommend that you contact support@movebank.org to let us know you are using the code. This way we can contact you if needed regarding updates or new features.

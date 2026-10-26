<?php

return [

     /*
    |--------------------------------------------------------------------------
    | Custom Config 
    |--------------------------------------------------------------------------
    */

    'url' => env('JIRA_URL', 'https://support.sextan.eu'),
    'assignee' => env('JIRA_USER_ASSIGNEE', 'support'),
    'user' => env('JIRA_USERNAME', 'support'),
    'pwd' => env('JIRA_PASSWORD', 'SGRsupport'),
    'auth' => env('JIRA_TOKEN', 'c3VwcG9ydDpTR1JzdXBwb3J0MTI')

];
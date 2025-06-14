<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IBM Cloud Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para servicios de IBM Cloud
    |
    */

    'cos' => [
        'api_key' => env('IBM_COS_API_KEY'),
        'service_instance_id' => env('IBM_COS_SERVICE_INSTANCE_ID'),
        'endpoint' => env('IBM_COS_ENDPOINT', 'https://s3.us-south.cloud-object-storage.appdomain.cloud'),
        'bucket_name' => env('IBM_COS_BUCKET_NAME'),
        'region' => env('IBM_COS_REGION', 'us-south'),

        // Cache settings
        'token_cache_key' => 'ibm_cos_token',
        'token_ttl' => 3300, // 55 minutos (los tokens IBM duran 1 hora)
    ],

    'watson_ml' => [
        'api_key' => env('IBM_WATSON_API_KEY'),
        'deployment_id' => env('IBM_WATSON_DEPLOYMENT_ID'),
        'space_id' => env('IBM_WATSON_SPACE_ID'),
        'job_id' => env('IBM_WATSON_JOB_ID'),
        'endpoint' => env('IBM_WATSON_ENDPOINT', 'https://api.dataplatform.cloud.ibm.com'),

        // Cache settings
        'token_cache_key' => 'ibm_watson_token',
        'token_ttl' => 3300, // 55 minutos

        // Job polling settings
        'status_check_interval' => 10, // segundos
        'max_status_checks' => 180, // máximo 30 minutos esperando
    ],

    'auth' => [
        'token_url' => env('IBM_AUTH_URL', 'https://iam.cloud.ibm.com/identity/token'),
        'grant_type' => 'urn:ibm:params:oauth:grant-type:apikey',
    ],
];
# Using the Tidal API

Tidal is...we will just say Tidal isn't offering public access to their API (even though they do appear to be
offering it to some services!) So this is a workaround of sorts.

Accessing their API isn't too difficult. You just need to determine the endpoints that their own application is
accessing. We will list some of those here, although I personally only spent my time finding the ones that pertain
to this service. If you want though, you can just install Fiddler or similar software and intercept the requests
being made to their own platform.

The difficult part is authentication. You need your redirect URL to be whitelisted in order to retrieve the OAuth
code on your own server. As such, we needed to be able to make the authentication request appear to be coming from
their own app, then retrieve the code from their own redirect URL.

Unfortunately, this means needing to actually monitor each URL that Tidal is redirecting to in the auth process.
This is possible, as we see below. However, because of built-in CORS protections in modern browsers, we can NOT do
this in the web version. Users will need to download the TS app and go through the auth process there. (or if you
want to implement this, you will need to have some non-web version of your app).

Note that if you just want to do something with the API for your own reasons and don't need to have user
authentication, you can simply retrieve the `access_token` and `refresh_token` in Chrome or Firefox while using
their web player. These will be valid as long as you continue to refresh the token and pass the token along in the
`Authorization` header.

## Authorization

First, we need to present the login page to the user. We will be using a `WebView` to do this in the iOS app.
`openAuthSessionAsync` doesn't let us monitor each new URL that is accessed unfortunately, so we need to use a
`WebView`.

We need to add the following GET parameters to the URL:

```text
appMode=WEB
client_id=CzET4vdadNUFQ5JU // This may change, so be prepared to get a new one. Ideally, you should dynamically get this to prevent any breaking of your application.
client_unique_key=<v4UUID> // Generate a v4 UUID. For example, https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
code_challenge=<PKCE token> // Generate a PKCE token. For example, https://stackoverflow.com/questions/75591478/php-get-token-oauth-pkce
code_challenge_method=S256
lang=en
redirect_uri=https://listen.tidal.com/login/auth
response_type=code
restrictSignup=true
scope=r_usr w_usr
autoredirect=true
```

Next, we will wait for the `WebView` to be directed to `https://login.tidal.com/login/auth`. The `code` parameter
will be passed along here, so we will retrieve it. Afterward, we will just close the `WebView` and continue the auth
process.

Finally, we will make a `POST` request to `https://tidal.com/oauth2/token`. We will need to pass along the following
parameters.

```text
client_id=CzET4vdadNUFQ5JU
client_unique_key=<key> // The one you generated earlier.
code_verifier=<verifier> // The SHA256 hash you generated earlier.
grant_type=authorization_code
redirect_uri=https://listen.tidal.com/login/auth
scope=r_usr w_usr
```

You will receive a response with all the user data (name, username, email, etc.) as well as the access token and
refresh token. You should keep note of those.

```json
{
    "user": {
        // All the user info, if you care.
    },
    "token_type": "Bearer",
    "access_token": "xxx",
    "refresh_token": "xxx",
    "expires_in": 86400
}
```

Do as you want!!

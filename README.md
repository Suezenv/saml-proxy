# 3S.lab SAML proxy

This project provides a proxy layer to act as a service provider between a frontend and a backend.

It has a few advantages :

* You don't expose a backend service that is not SAML aware directly
* You don't need to add SAML features to your backend
* It integrates with Heimdall for permissions checks

And a few drawbacks :

* For now, the only user provider is heimdall
* It adds a layer which can failed so it could be more difficult to debug errors

## Configuration

The project relies on environment variables to personalize its behavior

| Variable | Default | Description |
| --- | --- | --- |
| `APP_ENV` | `prod` | Symfony app environment |
| `APP_SECRET` | `95cb0a072426016024b542abd05ba877` | Symfony app secret |
| `APP_HOST` | `http://localhost:4000` | Hostname of this proxy app (mainly used to reference itself as a service provider |
| `SAML_USERNAME_ATTRIBUTE` | `entryUUID` | SAML username attribute |
| `SAML_GROUP_ATTRIBUTE` | `groups` | SAML groups attribute (contains name of the groups the user is member of in the saml identity provider. It adds roles to the SF user `ROLE_GROUP_{uppercase(group name)}`) |
| `SAML_IDP_METADATA_URI` | `simplesaml/saml2/idp/metadata.php` | URI of the IDP metadata |
| `SAML_IDP_ENTITY_ID` | `http://localhost:8443/simplesaml/saml2/idp/metadata.php` | Entity id of the IDP |
| `SAML_IDP_SSO_URI` | `http://localhost:8443/simplesaml/saml2/idp/SSOService.php` | Single Sign On URI of the IDP |
| `SAML_IDP_SLO_URI` | `http://localhost:8443/simplesaml/saml2/idp/SingleLogoutService.php` | Single Log Out URI of the IDP |
| `SAML_IDP_X509CERT` | ~ | IDP X509 certificate content as a string |
| `SAML_SP_X509CERT` | ~ | SP X509 certificate for this proxy app referenced in IDP |
| `SAML_SP_PRIVATEKEY` | ~ | SP private key of the SP X509 certificate for this proxy app |
| `SAML_ALWAYS_USE_DEFAULT_TARGET_PATH` | `false` | If true, redirect to SAML_DEFAULT_TARGET_PATH after login |
| `SAML_DEFAULT_TARGET_PATH` | `/` | url to redirect after login if SAML_ALWAYS_USE_DEFAULT_TARGET_PATH is true |
| `MONOLOG_MAIN_PATH` | `%kernel.logs_dir%/%kernel.environment%.log` | Monolog main handler path. Useful in docker to set `php://stderr` |
| `MONOLOG_DEPRECIATION_PATH` | `%kernel.logs_dir%/%kernel.environment%.deprecations.log` | Monolog depreciation handler path. Useful in docker to set `php://stderr` |
| `CORS_ALLOW_ORIGIN` | <code>^https?://\(localhost&#124;127\.0\.0\.1)(:[0-9]+)?$</code> | Control the CORS access origin header value |
| `CORS_ALLOW_CREDENTIALS` | `true` | Control the CORS allow credentials header value |
| `ERROR_REDIRECT` | `false` | If true, redirect to `ERROR_AUTHORIZATION_REDIRECT_HOST` or `ERROR_DEFAULT_REDIRECT_HOST` on error |
| `ERROR_AUTHORIZATION_REDIRECT_HOST` | ~ | URL to redirect in case of authentication or authorization error if `ERROR_REDIRECT` is `true` |
| `ERROR_DEFAULT_REDIRECT_HOST` | ~ | URL to redirect in case of other errors if `ERROR_REDIRECT` is `true` |
| `BACKEND_USER_ROLE` | `ROLE_USER` | The role the authenticated user needs to have to access the proxied service. The role come from the User.userType associate like 'ROLE_{userType} |
| `BACKEND_HOST` | `http://localhost:4010` | The host of the proxied service |
| `BACKEND_HEADERS` | `[]` | Headers passed to call to the proxied service |
| `HEIMDALL_HOST` | `http://localhost:4010` | Hostname of the heimdall service to load authenticated user roles and permissions |
| `HEIMDALL_API_KEY` | ~ | API key of the heimdall service |

## Usage

Right now, it needs the heimdall service (a private permission solution used at 3slab) but you can easily add your own 
user provider for your own permission system. Feel free to provide a PR if you have a standard solution.

```
docker build -t 3slab-saml-proxy -f .
docker run -e ENV=value,ENV2=value2 -p 8888:80 --name saml-proxy 3slab-saml-proxy
```

You can send it into your private repository like this : 

Create a release on github and retrieve the version.

```
export SAML_PROXY_VERSION=<version>
export MY_PRIVATE_REPO=<private-repo>
docker tag 3slab-saml-proxy $MY_PRIVATE_REPO/saml-proxy:$SAML_PROXY_VERSION && docker push $MY_PRIVATE_REPO/saml-proxy:$SAML_PROXY_VERSION
```

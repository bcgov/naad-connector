# NAAD Connector
A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local

#### Devcontainer
To run in the devcontainer (see .devcontainer/ directory):
1. Install the Dev Containers VSCode extension.
1. Run `cp .devcontainer/sample.override.env .devcontainer/override.env` and add/remove any env variable overrides you need based on your environment.
    - Note: this step can be skipped if you do'ot need to override any env variables.
1. View > Command Palette... > Dev Containers: Reopen in Container
1. VSCode should relaunch and the terminal should look like this: `vscode ➜ /workspaces/naad-connector`
1. This terminal should allow most commands to be run without any further dev environment setup:
    - `php src/start.php` to start the application
    - `composer test` to run unit tests
    - `composer test-coverage` to generate unit test coverage reports
    - `composer phpcs` to run linting
    - `composer migrate` to run database migrations

#### Docker deployment
To build and run via Docker:
```sh
docker build -t bcgovgdx/naad-app .
docker run --rm  bcgovgdx/naad-app
```

#### Docker-Compose deployment

- Prerequisites: rename the `./sample-env` to `.env` and fill in the values for local use only.

```shell
docker compose up
```

#### Docker Desktop Kubernetes deployment
To build and run in Kubernetes via Docker Desktop:
Note: Kubernetes must be enabled in Docker Desktop.
```sh
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/overlays/local
```

PHPMyAdmin will then be accessible at http://0.0.0.0:31008. You may need to use Firefox or Safari to access this as Chrome may block this address due to it not using https.

### OpenShift Build

```sh
# Change nameplate
oc project 12345-tools
oc apply -k deployments/kustomize/image-builds --namespace=12345-tools
oc start-build naad-app --follow --namespace=12345-tools
```

### OpenShift Deploy

```sh
oc project 12345-tools
oc apply -k deployments/kustomize/base --namespace=12345-tools
```

### View the database tables (Local only)

- visit http://0.0.0.0:8082 to see the phpMyAdmin page for the naad_connector database. It includes the latest migrations and all alerts that have been recorded.

_note:  this is mapped to port 8082 to avoid conflict with our wordpress containers_

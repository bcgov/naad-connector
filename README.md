[![Lifecycle:Experimental](https://img.shields.io/badge/Lifecycle-Experimental-339999)]()
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=bcgov_naad-connector&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=bcgov_naad-connector)

# NAAD Connector

A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

## Secrets
> Vault secrets are not going to be used for MVP.
- production deployments will use vault secrets, there is code that either accepts secrets in the form of env variables or for example `export MY_SECRET=password` or a vault file called `/vault/secrets/MY_SECRET`

### Local Deployment

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
    - `composer phpdoc` to generate project documentation

#### Docker Desktop Kubernetes deployment

To build and run in Kubernetes via Docker Desktop, follow these steps:

```shell
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/overlays/local

# or use composer instead:
composer k8s-start
```

**Note:** Kubernetes must be enabled in Docker Desktop.

Stopping (deleting) all pods will destroy all alerts in the database. To stop all pods, run:

```shell
kubectl delete -k deployments/kustomize/overlays/local

# or use composer instead:
composer k8s-delete
```

---

### Accessing PHPMyAdmin to view your alerts table

After deployment, PHPMyAdmin will be accessible at the following local addresses:

- Docker Compose: <http://0.0.0.0:8080>
- Kubernetes: <http://0.0.0.0:31008>

**Note:** You may need to use Firefox or Safari to access this as Chrome may block this address due to it not using https.

---

#### Gitignored Folders
##### secrets
- this folder is to capture the vault secrets 
- to test a vault secret, simply add the sescret as a file identical to the secret name, and add your secret into the file.
- example `secrets/DESTINATION_PASSWORD`

##### logs
- this folder captures the logs

### Remote Deployment

#### OpenShift Image Build

1. Log in to your OpenShift WEB console, and choose the naad-app namespace.
2. copy the login command from the OpenShift Web console.
3. paste the login command into your terminal.
4. switch to the correct (12345-tools) namespace and apply the base configuration:

```shell
# Change to the correct namespace
oc project 12345-tools # Replace with your namespace

# Apply the image builds configuration
oc apply -k deployments/kustomize/image-builds

# Start the build process and follow the output
oc start-build naad-app --follow
```

##### Openshift build testing

1. follow steps 1 - 3 above
2. edit `deployments/kustomize/image-builds/app.yaml`:
    - change `spec.source.git.ref` to your feature branch you want to test
3. follow step 4 above

#### OpenShift Deploy

1. Log in to your OpenShift WEB console, and choose the naad-app namespace.
2. copy the login command from the OpenShift Web console.
3. cd to your `tenant-gitops-<12345>` repository in your terminal.
4. paste the login command into your terminal.
5. switch to the correct (12345-dev) namespace.
6. (OPTIONAL - for testing) edit your kustomization file to replace the value of `resources:` to:
    - `https://github.com/bcgov/naad-connector//deployments/kustomize/overlays/openshift?ref=<your-feature-branch>`
7. apply the base configuration:

```shell
# Change to the correct namespace
oc project 12345-dev # Replace with your namespace

# from your tenant root, apply the Kustomization file
oc apply -k deployments/kustomize/dev
```

### OpenShift Tekton Pipeline

#### Overview
This pipeline automates the build process in OpenShift Pipelines. It detects changes in the `src/` directory and rebuilds the `naad-app` image only if needed.

#### Workflow
1. **Triggers**:
   - Automatically runs when files in `src/` change (`.github/workflows/build.yml`).
   - Can be manually triggered using GitHub Actions' "Run workflow" button.

2. **Pipeline Execution**:
   - The pipeline (`build-pipeline.yaml`) starts the `start-build` task.
   - The task checks if changes require a rebuild.
   - If changes are detected, `oc start-build naad-app --wait` runs.
   - On success, the image is tagged as `dev`.

3. **How to Manually Run the Pipeline**
   - Run the following command in OpenShift:
     ```
     oc create -f openshift/tekton/pipelineruns/build-pipelinerun.yaml
     ```

### E2E Testing

End to end testing is done by using e2e testing socket server to replace the real NAADS socket allowing us to send any alert XML we want to test the entire NaadConnector system to ensure it's working as expected when receiving a given alert.

#### Local

##### Devcontainer

1. Add an entry to `.devcontainer/override.env` to set `NAAD_URL=0.0.0.0` to cause the application to connect to the e2e testing socket server.
1. Rebuild the devcontainer (`View > Command Pallette > Dev Containers: Rebuild Container`) so it's using the new env from step 1.
1. In a devcontainer terminal, run `php tests/e2e/start.php` to start the e2e testing socket server.
1. In another devcontainer, run `php src/start.php` to start the application which should connect to the testing socket server.

##### Kubernetes

1. Build the image (`composer build`).
1. Apply the e2e overlay (`kubectl apply -k deployments/kustomize/overlays/e2e`).
  - Note: Not currently working due to connection issues between socket server and client.

### Generate & View Documentation with phpDocumentor

The only command you need is `composer phpdoc`. It runs a script that pulls and runs the phpdoc container, generates documentation, and opens a browser so you can view it assuming you run the command from the root directory of your project.

#### Configuration

To configure the phpdoc further, look at `phpdoc.dist.xml` in the root directory of the project. For now it only determines the output folder `/docs` where the `index.html` is found, and the source folder(s) where the code that will be documented is found.

#### Viewing Documentation

To view the documentation, you can use one of the following methods:

- Run `composer phpdoc-view`
- Navigate to `./docs` in your console and type `open index.html`

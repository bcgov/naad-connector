# Workflows

## PR Open
When a pull request is opened or changed, the following steps are performed:

1. **Linting and Unit Testing** – Ensures code quality and correctness.
1. **Image Build and Hosting** – A new container image is built and pushed to the https://github.com/bcgov/naad-connector/pkgs/container/naad-connector%2Fnaad-connector. The image is tagged with the PR number (e.g., 101 for PR #101) and also with the dev tag.
1. **Security Scanning with Trivy** – After the image is built, it is scanned using https://github.com/aquasecurity/trivy to detect vulnerabilities. The scan results can be found under the Security tab.

Every time the PR is updated, the workflow is triggered again, rebuilding and rescanning the image. The previously built image with the same PR number tag is overwritten.

## Merge
When a pull request is merged into the `main` branch, the current `dev` image is given the `test` and `latest` tags.

**NOTE:** If multiple PRs are open at the same time the `dev`-tagged image may not be the image that was built from the PR that was merged if another PR has been changed after the merge was performed. We should avoid opening multiple PRs at the same time.

## Release
When a release is created, the current `latest` tag is given the `prod` and a version tag using the release's version tag, eg. if a v2.1.1 release is created, the `latest` image will also be tagged with `v2.1.1`.

**NOTE:** Ensure that the Merge workflow has fully completed and the correct image has been given the `latest` tag before creating the release or the wrong image could be given the release tag due to timing issues.

## Scheduled Security Scans with Trivy

This project uses https://github.com/aquasecurity/trivy to perform daily vulnerability scans on the container image hosted in GitHub Container Registry (GHCR). You can also manually trigger the scan via the **Actions** tab in GitHub.

### Output

The scan results can be viewed in the **Security** tab in GitHub.

# Automated Testing

 - PHP linting on CI build_test
 - PHP Code Sniffer on CI build_test
 - PHP Code Sniffer on git pre-commit hook installed with composer.
 - Other automated testing may be added and is encouraged

There is lando tooling for phpcs, phpcbf and phpmd. Just pass in a file or directory.

i.e. `lando phpcbf web/modules/custom/my_module`

When you commit code `phpcs` runs and will show you a detailed list of standards violations and php errors in the committed files. Please clean them up before committing code to the repo. `phpcbf` can be used to automaticaly clean up many minor code style issues.

# Contributing to Vadnica Bank

Thank you for your interest in contributing to Vadnica Bank!

## Reporting bugs

Before opening a new issue, please check whether a similar issue already exists.

When reporting a bug, include:

- a short and clear description of the problem,
- the steps needed to reproduce it,
- the expected behavior,
- the actual behavior,
- your PHP version and operating system,
- any relevant error messages.

Never publish real banking data, passwords, secret keys, or the contents of `db.php`.

## Feature requests

Please describe:

- what you would like to add,
- why the feature would be useful,
- how it should work,
- any potential security implications.

## Code changes

1. Create a branch from `main`:
   ```bash
   git checkout -b name-of-change
   ```

2. Test your changes locally.

3. Check that you are not adding sensitive data:
   ```bash
   git status
   ```

4. Create a clear commit:
   ```bash
   git add .
   git commit -m "Describe the change"
   ```

5. Push the branch to GitHub:
   ```bash
   git push -u origin name-of-change
   ```

6. Open a pull request against the `main` branch.

## Security

Do not publicly report security vulnerabilities as ordinary issues. Before reporting a vulnerability, remove all sensitive information and use a private contact method to reach the project maintainer.

## License

Contributions to this project are released under the same terms as the project, in accordance with the GNU AGPL v3 license.

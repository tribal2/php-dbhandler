# Contributing

Contributions are welcome. To maintain the quality and consistency of the codebase, we have set a few guidelines for contributors:

## Contribution Process

1. **Fork and Clone**: Start by forking the repository and cloning your fork to your local machine.
2. **Create a New Branch**: For each new feature or fix, create a new branch from `develop`.
3. **Develop Your Feature or Fix**: Implement your changes in your branch. Be sure to adhere to the existing code style.
4. **Commit Your Changes**: Commit your changes to your branch. Please follow the [Conventional Commits](#using-conventional-commits) specification for your commit messages.
5. **Test Your Changes**: Before submitting your contribution, ensure all tests pass. See [Testing with Pest](#testing-with-pest) for more information.
6. **Submit Your Contribution**: Push your changes to your fork on GitHub and submit a pull request from your feature or fix branch to the 👉`develop`👈 branch of the original repository. See [Submitting Contributions](#submitting-contributions) for more information.

## Using Conventional Commits

For this project, we adopt the [Conventional Commits](https://www.conventionalcommits.org/) specification for our commit messages. This standardization helps in maintaining a clear and readable history of our project. It also facilitates the automation of our release notes and versioning processes.

When contributing, please ensure your commit messages follow this style. Here are some examples:

- `feat(Where): add isTrue() assertion`
- `fix(Db): correct typo in the main interface`
- `docs: update README with new API documentation`
- `refactor(Select): rewrite the from() method`

For more information and examples, please refer to the [Conventional Commits website](https://www.conventionalcommits.org/).

## Using the Development Container

This project includes a Visual Studio Code development container configuration (devcontainer) to facilitate a consistent development environment for all contributors. The devcontainer setup ensures that everyone works with the same tools and dependencies, reducing "works on my machine" problems.

### Prerequisites

- [Visual Studio Code](https://code.visualstudio.com/)
- [Docker](https://www.docker.com/products/docker-desktop)
- [Remote - Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) for Visual Studio Code

### Getting Started with the DevContainer

1. **Clone the Repository**: First, clone the repository to your local machine.
2. **Open with VS Code**: Open the cloned repository folder in Visual Studio Code.
3. **Set .env File**: Copy the `.devcontainer/.env.sample` file to `.devcontainer/.env` and update the values as needed.
4. **Reopen in Container**: When prompted, or by using the Command Palette (`Ctrl+Shift+P`), choose "Reopen in Container". This will build and start the development container defined in `.devcontainer/devcontainer.json`.
5. **Start Coding**: Once the container is built and running, you can start coding with all the necessary dependencies and tools pre-installed.

### Benefits

- **Consistent Development Environment**: Everyone uses the same OS, tools, and configurations.
- **Easy to Start**: New contributors can get started quickly without the need to configure their local environment for development.
- **Isolated Environment**: Reduces the risk of impacting your local system's setup.

### Tips

- **Accessing the Terminal**: You can access the integrated terminal in VS Code to run commands inside the container.
- **Debugging**: The devcontainer setup can include debugging tools specific to the project, accessible within VS Code.

Feel free to utilize the development container for an easier and more consistent contribution experience!

## Testing with Pest

We use [Pest](https://pestphp.com/docs/expectations) for writing expressive tests. Pest is a delightful PHP Testing Framework with a focus on simplicity. It's an ideal choice for writing both unit tests and feature tests for PHP applications.

> If you're a JavaScript developer familiar with Jest, you'll find Pest comfortably similar. Its syntax and functionality are inspired by Jest, making it intuitive for those who have experience with JavaScript testing frameworks. This familiarity helps to ease the learning curve and allows you to quickly become productive in writing tests for PHP applications.

### Writing Tests

- **Include Unit Tests**: For every new feature or bug fix, include corresponding unit tests that validate your changes.
- **Feature Tests**: Along with unit tests, feature tests that demonstrate the functionality of new features or the resolution of bugs are highly appreciated.
- **Test Coverage**: Aim to maintain or improve the current test coverage of the project.

### Running Tests

Before submitting your contribution, ensure all tests pass:

```bash
vendor/bin/pest --coverage
vendor/bin/pest --type-coverage
```

> **Note**:
> - The `--coverage` and `--type-coverage` flags are optional and will generate a coverage report.

## Submitting Contributions

1. **Push Your Changes**: Push your changes to your fork on GitHub.
2. **Create a Pull Request**: Submit a pull request from your feature or fix branch to the 👉`develop`👈 branch of the original repository.
3. **Describe Your Changes**: In your pull request, provide a clear description of the changes and reference any related issue(s).

## Review Process

Our maintainers will review your pull request. During the review process, maintainers or other contributors might suggest changes. Continuous collaboration and communication will be key to getting your contribution merged into the project.

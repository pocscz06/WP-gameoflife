## Table of Contents

- [Description](#wp-gameoflife)
- [Authors](#authors)
- [Project Structure](#project-structure)
- [Instructions](#instructions-to-build-and-run)

# WP-gameoflife

The Game of Life involves a grid of cells where each cell has one of two states: alive or dead. The basic premise revolves around an “initial configuration” that “evolves” through time based on its neighbors (8 adjacent cells) via these mathematical rules:

- A live cell with fewer than two live neighbors dies (underpopulation)
- A live cell with more than three live neighbors dies (overpopulation)
- A live cell with two or three live neighbors lives on to the next generation
- A dead cell with exactly three live neighbors becomes a live cell

# AUTHORS

KENNY PHAM
STEVEN PHAN
LAW REH

## PROJECT STRUCTURE

```
WP-gameoflife/
├── frontend/
    └── src/
        └── assets/
        └── components/
        └── styles/
├── backend/
    └── TBA
├── database/
    └── TBA
```

frontend/:

- CSS/JS
- React bootstrapped with Vite for initialization
- Contains all of our UI/UX implementation
- Tailwind CSS for ease of modern styling
- [React Router](https://reactrouter.com/6.29.0/start/overview) for Routing

> [!NOTE]
> Most frontend implementation will be done within the src/ folder. assets/ for external images, components/ for reusable UI components, and styles/ for our stylesheets.
> App.jsx is the root application file. It is used for global configuration (global stylesheet), or for global components (i.e., headers, footers, sidebars, etc.)
> As we will only have a few (maybe 3-4) separate pages, I am opting to not include a pages/ folder--we will just create our pages within the src/ dir.

backend/:

- PHP
- Contains backend logic for form validation, user authentication, and communication with the database

database/:

- SQL (MySQL)

# Instructions to Build and Run

Prerequisites:

- [Node.js](https://docs.npmjs.com/cli/v11/configuring-npm/install)
- NPM (Node Package Manager; comes with Node.js)
- [MySQL](https://dev.mysql.com/downloads/installer/)

As our build directories are ignored by convention via .gitignore, whenever you pull changes from the repository that include additions to project dependencies, you should run:

1. Install NPM packages within the terminal to install new dependencies

```
npm install
```

2. Start development server (for testing/demo)

```
npm run dev
```

3. Start PHP development server

```
cd backend

php -S localhost:8000
```

> [!NOTE]
> Both servers need to be running simultaneously to test the full-stack implementation of the project. If you just run "npm run dev," you will only be testing the frontend.
> Developers typically use PHP servers to serve the PHP files rather than the built-in PHP server (like above); we could as well if you guys desire

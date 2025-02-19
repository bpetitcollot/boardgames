# Boardgames

## What is this about ?

This is an app intended to let people play many boardgames online.

The original goal is to replace my (very) old webapp allowing to play the game
Innovation with only two players.

This new version aims to allow playing the game with more than two players and
watching all the actions that happend during a game thanks to an event sourcing
system.

### Work in progress

- A game of Solitaire (aka Peg Solitaire) will be first implemented as a POC of
  game rules management
- Checkers will follow to introduce players interactions
- Innovation will be the next challenge with up to five players involved and
  complex game rules

More details on the [Github Project](https://github.com/users/bpetitcollot/projects/3)

### Tech challenges

In a time of troubles with frontend stack choices, this project is also a place
for experiments with replacement candidates of my usual stack (REST API with
Symfony / React and NextJS front app)

## Requirements

- PHP 8.2
- PostgreSQL 14

## Install

Configure your database and run doctrine migrations :

```bash
bin/console doctrine:migrations:migrate
```

Then run a PHP server at the root dir.


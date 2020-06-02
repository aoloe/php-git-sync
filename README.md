# github-sync

Simple PHP script (and instructions) for using git to keep a web project in sync with a Github repository.

> This is an unfinished effort to do the sync without using git.  
> The direction seems to be the good one, but I fear of what happens if messages get lost.  
> How to restore a sane state?

It uses `git reset` and `git pull` to to force the _tracked_ files on your server to match the ones in the Github repository:

- It will only reset files that are tracked through Git.
- It won't change any other files.
- Local changes in the tracked files will be undone.

Git must be installed on the server.

You can use one sync script for multiple projects and branches, each in their own directory.

For your safety, only use this script for actions that are non destructive and which could be triggered at anytime by anybody from anywhere in the internet.

## Installing

- Add this script to your website (as an example as `sync/index.php`).
- Create a `config.php` based on `config-demo.php`.

## Setup the webhook on Github

- In the settings, add a webhook targeting this PHP script.
  - url: path to this script (`http://your-server.org/sync/`)  
    (the final `/` might matter).
  - Select json as the content type.
  - Define a secret (you will put the same in your `config.php` file.
- Each time your repository gets pushed, a `git reset` it will be triggered on your server.

## Todo

- support other commands (mostly a call to shell scripts) after having synced.

## Resources

- <https://gist.github.com/jplitza/88d64ce351d38c2f4198>: A basic PHP webhook handler for Github, just verifying the signature and some variables before calling another command to do the actual work
- https://developer.github.com/v3/guides/managing-deploy-keys/
- https://gist.github.com/zhujunsan/a0becf82ade50ed06115
- https://www.justinsilver.com/technology/github-multiple-repository-ssh-deploy-keys/
- https://gist.github.com/jexchan/2351996
- https://stackoverflow.com/questions/4565700/specify-private-ssh-key-to-use-when-executing-shell-command-with-or-without-ruby

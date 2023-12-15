#!/usr/bin/bash

# DbHandler
alias tests='XDEBUG_MODE=off /dbhandler/src/vendor/bin/phpunit --testdox /dbhandler/src/tests'
alias borrarlogs='find /dbhandler/src -iname "*.log" -delete'
alias vermod='find /dbhandler/src -mtime -1'
alias verlogs='find /dbhandler/src -mtime -1 -iname "*.log"'


alias c="clear"
alias cdc="cd ~; clear;"
alias h='history'
alias hg='history | grep -i'
alias :q="exit"
alias ..="cd .."

alias dev="cd ~/dev"

alias findx="find $HOME -iname"
alias follow="tail -f -n +1"
alias biggest="du -h --max-depth=1 | sort -h"
alias sizes="du -shc"

alias sha1='openssl sha1'
alias pingg="ping 8.8.8.8"

## Colorize the grep command output for ease of use (good for log files)##
alias grep='grep --color=auto'
alias egrep='egrep --color=auto'
alias fgrep='fgrep --color=auto'

# confirmation #
alias mv='mv -i'
alias cp='cp -ip'
alias ln='ln -i'

# update on one command
alias update='sudo apt-get update -y \
  && sudo apt-get upgrade -y \
  && sudo apt-get autoremove'

# become root #
alias root='sudo -i'
alias su='sudo -i'

# enable color support of ls and also add handy aliases
if [ -x /usr/bin/dircolors ]; then
    test -r ~/.dircolors && eval "$(dircolors -b ~/.dircolors)" || eval "$(dircolors -b)"
    alias ls='ls --color=auto'
    #alias dir='dir --color=auto'
    #alias vdir='vdir --color=auto'

    alias grep='grep --color=auto'
    alias fgrep='fgrep --color=auto'
    alias egrep='egrep --color=auto'
fi

# some more ls aliases
alias ll='ls -lahvF --group-directories-first'
alias la='ls -A'
alias l='ls -CF'

# Go to manpage parameter description (eg: $ mans ls -l)
function mans {
  man $1 | less -p "^ +$2"
}

# find-in-file - usage: fif <SEARCH_TERM>
fif() {
  if [ ! "$#" -gt 0 ];
  then
    echo "Need a string to search for!";
    return 1;
  fi

  # @TODO: use grep if rg is not available
  if command -v rg &> /dev/null
  then
    rg --files-with-matches --no-messages "$1" \
      | fzf $FZF_PREVIEW_WINDOW --preview "rg --ignore-case --pretty --context 10 '$1' {}"
  fi
}

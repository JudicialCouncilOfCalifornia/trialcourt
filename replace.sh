#!/bin/bash

# Get a list of all files with merge conflicts
conflicted_files=$(git diff --name-only --diff-filter=U
)

for file in $conflicted_files; do
    echo "Resolving conflict in $file..."

    # Backup original file
   # cp "$file" "$file.bak"

    # Use 'git checkout' to accept both changes
    # First, accept 'ours', then append 'theirs' at the end of the file
    git checkout --ours "$file"
    echo "======= THEIRS ========" >> "$file"
    git checkout --theirs "$file" >> "$file"

    # Alternatively, for a simpler merge without markers:
    # git show :1:"$file" > "$file" # Base
    # echo "======= OURS ========" >> "$file"
    # git show :2:"$file" >> "$file" # Ours
    # echo "======= THEIRS ========" >> "$file"
    # git show :3:"$file" >> "$file" # Theirs

    echo "Conflict resolved by appending both changes in $file."
done

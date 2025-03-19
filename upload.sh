#!/bin/bash

# Colors for terminal output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default commit message
DEFAULT_MESSAGE="Update Laravel API"

# Get commit message from argument or use default
COMMIT_MESSAGE=${1:-$DEFAULT_MESSAGE}

echo -e "${YELLOW}Starting GitHub upload process...${NC}"

# Check if we're in a git repository
if [ ! -d .git ]; then
  echo -e "${YELLOW}Not in a git repository. Initializing...${NC}"
  git init
  git remote add origin https://github.com/pak-lol/api-skeleton-laravel.git
fi

# Stage all changes
echo -e "${BLUE}Staging all changes...${NC}"
git add .

# Commit with the provided message
echo -e "${BLUE}Committing changes with message: ${COMMIT_MESSAGE}${NC}"
git commit -m "$COMMIT_MESSAGE"

# Push to GitHub
echo -e "${BLUE}Pushing to GitHub...${NC}"
git push -u origin master

# Check if push was successful
if [ $? -eq 0 ]; then
  echo -e "${GREEN}Successfully uploaded to GitHub!${NC}"
else
  echo -e "${YELLOW}Push failed. You might need to pull changes first or check your credentials.${NC}"
  echo -e "Try running: git pull origin master"
fi

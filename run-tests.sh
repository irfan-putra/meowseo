#!/bin/bash
# Test runner script for MeowSEO

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Composer is not installed. Please install composer first."
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install --no-interaction
fi

# Run PHPUnit tests
echo "Running tests..."
./vendor/bin/phpunit

echo "Tests completed!"

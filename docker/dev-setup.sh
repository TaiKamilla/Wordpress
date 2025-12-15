#!/bin/bash

set -e

# Function to print colored messages
print_message() { 
    local message=$1
    echo -e "${message}"
}

print_header() {
    echo ""
    print_message "=========================================="
    print_message "$1"
    print_message "=========================================="
}

# Install WordPress
install_wordpress() {
    print_header "Installing WordPress"

    # Check if WordPress is already installed
    if wp core is-installed --allow-root 2>/dev/null; then
        print_message "WordPress is already installed. Skipping installation..."
        return
    fi

    local site_url="${WP_SITE_URL:-http://localhost}"
    local site_title="${WP_SITE_TITLE:-Wordpress Dev Site}"
    local admin_user="${WP_ADMIN_USER:-admin}"
    local admin_password="${WP_ADMIN_PASSWORD:-admin123}"
    local admin_email="${WP_ADMIN_EMAIL:-info@acme.com}"

    print_message "Installing WordPress..."
    wp core install \
        --url="${site_url}" \
        --title="${site_title}" \
        --admin_user="${admin_user}" \
        --admin_password="${admin_password}" \
        --admin_email="${admin_email}" \
        --skip-email \
        --allow-root

    print_message "WordPress installed successfully"
    print_message "Admin URL: ${site_url}/wp-admin"
    print_message "Username: ${admin_user}"
    print_message "Password: ${admin_password}"
}

# Create VS Code configuration
create_vscode_config() {
    print_header "Creating VS Code Configuration"

    local vscode_dir=".vscode"
    mkdir -p "${vscode_dir}"

    # Create launch.json for Xdebug
    cat > "${vscode_dir}/launch.json" <<EOF
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "\${workspaceFolder}/wordpress"
            },
            "log": true
        }
    ]
}
EOF

    # Create settings.json
    cat > "${vscode_dir}/settings.json" <<EOF
{
    "php.validate.executablePath": "/usr/local/bin/php",
    "phpunit.phpunit": "vendor/bin/phpunit",
    "phpunit.args": [
        "-c",
        "phpunit.xml"
    ]
}
EOF

    print_message "VS Code configuration created"
    print_message "Launch.json and settings.json created in .vscode/"
}

# Main execution
main() {
    cd /var/www/html || exit 1
    print_header "WordPress Development Environment Setup"
    install_wordpress
    create_vscode_config
}

# Run main function
main "$@"

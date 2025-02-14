# Google Sheets API Integration

This project demonstrates integration with Google Drive API using Symfony.

## Prerequisites

- VSCode and dev containers extension

## Setup Instructions

### Google Cloud Configuration

1. Create a new Google Cloud project at [Google Cloud Console](https://console.cloud.google.com)
2. Enable the Google Drive API:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "Enable"
3. Configure OAuth credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client ID"
   - Download the credentials file

### Project Configuration

1. Clone this repository
2. Place the downloaded credentials file at:
   ```
   src/Controller/client_secret.json
   ```

## Running the Application

1. Start the Symfony development server:
   ```bash
   symfony server:start
   ```

2. Access the application at:
   ```
   http://localhost:8001/api/sheets
   ```


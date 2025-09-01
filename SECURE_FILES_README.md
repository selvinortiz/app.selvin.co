# Secure File Downloads

This system allows you to securely share files with clients using password protection and download limits.

## Features

- **Password Protection**: Optional password required to download files
- **Download Limits**: Set how many times a file can be downloaded (1 = one-time use)
- **Expiration Dates**: Set when files expire and become unavailable
- **Client Association**: Optionally associate files with specific clients
- **Clean UI**: Professional download page for clients

## How to Use

### 1. Upload a File

1. Go to your Filament admin panel
2. Navigate to "Secure Files" in the sidebar
3. Click "Create" to upload a new file
4. Fill in the details:
   - **File**: Upload your file (PDF, ZIP, images, etc.)
   - **Display Name**: What clients will see
   - **Associated Client**: Optional - link to a specific client
   - **Password**: Optional - leave empty for no password
   - **Download Limit**: How many times it can be downloaded (1 = one-time)
   - **Expiration Date**: Optional - when the file expires

### 2. Share with Clients

After creating a secure file, you'll get a unique download link. You can:

- Copy the link from the admin panel
- Use the "Copy Link" action in the file list
- The link format is: `https://yourapp.com/download/{token}`

### 3. Client Experience

When clients visit the download link:

1. They see a professional download page
2. If password is required, they enter it
3. File downloads and the count increments
4. If limit is reached, the file becomes unavailable

## Example Use Cases

### W-9 Form (One-time, Password Protected)
- Upload W-9 form
- Set password: "w9-2024"
- Set download limit: 1
- Set expiration: 30 days from now
- Share link with client

### Project Files (Multiple Downloads)
- Upload project ZIP
- No password required
- Set download limit: 5
- Set expiration: 90 days
- Share with client team

### Invoice PDF (Simple)
- Upload invoice
- No password
- Download limit: 3
- No expiration
- Share with client

## Technical Details

### Database Schema

```sql
secure_files:
- id
- tenant_id (auto-set by Filament)
- user_id (auto-set by current user)
- client_id (optional)
- name (display name)
- filename (original filename)
- file_path (storage path)
- mime_type
- file_size
- access_token (32-char unique token)
- password (optional)
- download_limit (default: 1)
- download_count (default: 0)
- expires_at (optional)
- timestamps
```

### Routes

- `GET /download/{token}` - Show download page
- `POST /download/{token}` - Process download

### Security Features

- Files stored in `storage/app/secure-files/`
- Access tokens are 32-character random strings
- Passwords are stored in plain text (simple implementation)
- Download counts prevent abuse
- Expiration dates auto-disable files
- Tenant isolation (users only see their tenant's files)

## Customization

### Styling
The download page uses Tailwind CSS. You can customize the styling in `resources/views/secure-files/download.blade.php`.

### File Types
Supported file types are configured in the Filament resource. Currently supports:
- PDF files
- ZIP files
- Images
- Text files

### Storage
Files are stored using Laravel's file storage system. You can change the disk in the Filament resource configuration.

## Troubleshooting

### File Not Found
- Check if the file exists in storage
- Verify the file_path is correct
- Ensure storage permissions are set

### Download Count Not Incrementing
- Check if the file is being served correctly
- Verify the database transaction is working

### Password Not Working
- Ensure passwords match exactly (case-sensitive)
- Check for extra spaces or characters

## Future Enhancements

- Email notifications when files are downloaded
- Audit logs for file access
- Bulk file upload
- File preview (for images/PDFs)
- Custom branding on download page
- Integration with invoice system


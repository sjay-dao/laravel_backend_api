<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RMS Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:20px;">

<div style="max-width:1000px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden;">

    <div style="background:#0f172a; color:white; padding:20px;">
        <h2 style="margin:0;">RMS Tag Notification</h2>
        <p style="margin:8px 0 0 0;">
            {{ count($records) }} record(s) require attention.
        </p>
    </div>

    <div style="padding:20px;">

        <p>
            Good day,
        </p>

        <p>
            The following RMS request(s) were detected by the notification scheduler.
        </p>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
            <thead>
                <tr style="background:#e2e8f0;">
                    <th align="left">ID</th>
                    <th align="left">Title</th>
                    <th align="left">Status</th>
                    <th align="left">Tag</th>
                    <th align="left">Created Date</th>
                </tr>
            </thead>

            <tbody>
                @foreach($records as $record)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td>{{ $record['id'] ?? '' }}</td>
                        <td>{{ $record['title'] ?? '' }}</td>
                        <td>{{ $record['requestor'] ?? '' }}</td>
                        <td>{{ $record['status'] ?? '' }}</td>
                        <td>{{ $record['priority'] ?? '' }}</td>
                        <td>{{ $record['category'] ?? '' }}</td>
                        <td>{{ $record['subcategory'] ?? '' }}</td>
                        <td>{{ $record['type'] ?? '' }}</td>
                        <td>{{ $record['Tag'] ?? '' }}</td>
                        <td>{{ $record['RequestDate'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

        <p>
            This email was generated automatically by the RMS notification service.
        </p>

    </div>

    <div style="background:#f8fafc; padding:15px; font-size:12px; color:#64748b;">
        RMS Notification Service by SJP Software Corporation
        <br>
        Generated: {{ now()->format('Y-m-d H:i:s') }}
    </div>

</div>

</body>
</html>
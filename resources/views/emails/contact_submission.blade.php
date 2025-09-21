<div style="font-family: Arial, sans-serif; line-height:1.6; color:#333;">
    <h2 style="color:#2c3e50;">📩 New Contact Us Submission</h2>
    <p>You have received a new contact message. Here are the details:</p>

    <table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr>
            <td style="font-weight:bold; width:120px;">Name:</td>
            <td>{{ $data['name'] }}</td>
        </tr>
        <tr style="background:#f9f9f9;">
            <td style="font-weight:bold;">Email:</td>
            <td>{{ $data['email'] }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Phone:</td>
            <td>{{ $data['phone'] ?? 'N/A' }}</td>
        </tr>
        <tr style="background:#f9f9f9;">
            <td style="font-weight:bold;">Message:</td>
            <td>{{ $data['message'] }}</td>
        </tr>
    </table>

    <p style="margin-top:20px;">Please follow up with this user as soon as possible.</p>
</div>

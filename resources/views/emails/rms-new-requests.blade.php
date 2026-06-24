<h2>New RMS Requests</h2>

<p>The following RMS requests have status <strong>New</strong>:</p>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Requestor</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($requests as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->Title }}</td>
                <td>{{ $item->Requestor }}</td>
                <td>{{ $item->CreatedDate }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
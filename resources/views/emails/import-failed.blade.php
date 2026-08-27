<head>
    <meta charset="UTF-8">
    <title>CSV Import Failed</title>
</head>

<body>
    <h2>CSV Import Failed</h2>

    <p>Your CSV Import has failed</p>
    <p><strong>File Name: </strong> {{$import->file_name}}</p>
    <p><strong>Total Records: </strong> {{$import->total_records}}</p>
    <p><strong>Processed Records: </strong> {{$import->processed_records}}</p>
    <p><strong>Failed Records: </strong> {{$import->failed_records}}</p>
    <p><strong>Status: </strong> {{$import->status}}</p>
    <p><strong>Error Message: </strong> {{$import->error_message}}</p>
</body>


<div>
    <!-- When there is no desire, all things are at peace. - Laozi -->
</div>

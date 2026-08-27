<head>
    <meta charset="UTF-8">
    <title>CSV Import Completed</title>
</head>  

<body>
    <h1>CSV Import Completed </h1>
    
    <p>Your CSV Import has been completed successfully</p>
    <p><strong>File: </strong> {{$import->file_name}}</p>
    <p><strong>Total records: </strong> {{$import->total_records}}</p>
    <p><strong>Processed records:</strong> {{ $import->processed_records }}</p>
    <p><strong>Failed records: </strong> {{$import->failed_records}}</p>
    <p><strong>Status: </strong> {{$import->status}}</p>

</body>

<div>
    <!-- Waste no more time arguing what a good man should be, be one. - Marcus Aurelius -->
</div>
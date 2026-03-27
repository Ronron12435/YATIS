<!DOCTYPE html>
<html>
<head>
    <title>Debug Info</title>
</head>
<body>
    <h1>User Debug Info</h1>
    <p><strong>User ID:</strong> {{ auth()->id() }}</p>
    <p><strong>Username:</strong> {{ auth()->user()->username }}</p>
    <p><strong>Role:</strong> {{ auth()->user()->role }}</p>
    <p><strong>Has Businesses:</strong> {{ auth()->user()->businesses()->count() }}</p>
    @if(auth()->user()->businesses()->first())
        <p><strong>First Business:</strong> {{ auth()->user()->businesses()->first()->name }}</p>
    @endif
</body>
</html>

@php
$documents = [
  ['title' => 'AI Report', 'description' => 'About AI'],
  ['title' => 'Business Plan', 'description' => 'Startup ideas']
];
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Smart Document Search</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body style="
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
    padding: 30px;
">

    <h1 style="margin-bottom: 20px;">🔍 Smart Document Search</h1>

    <!-- 🔹 Search Form -->
    <form method="GET" action="/search" style="
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    ">

        <!-- Search input -->
        <input type="text" name="query" placeholder="Search documents..." style="
            padding: 12px;
            width: 250px;
            border-radius: 8px;
            border: 1px solid #ccc;
        ">

        <!-- Category filter -->
        <select name="category" style="padding: 12px; border-radius: 8px;">
            <option value="">All Categories</option>
            <option value="General">General</option>
            <option value="Reports">Reports</option>
        </select>

        <!-- Author filter -->
        <select name="author" style="padding: 12px; border-radius: 8px;">
            <option value="">All Authors</option>
            <option value="Admin">Admin</option>
            <option value="User1">User1</option>
        </select>

        <!-- Submit button -->
        <button type="submit" onclick="fakeSearch()" style="
            padding: 12px 20px;
            background: #6c63ff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        ">
            <i class="fas fa-search"></i> Search
        </button>

    </form>

    <!-- ⏳ Loading -->
    <div id="loading" style="display:none; font-size:18px; margin-bottom:20px;">
        ⏳ Searching...
    </div>

    <!-- 📄 Results -->
    <div id="results">

        @if(count($documents) > 0)
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            ">
                @foreach($documents as $doc)
                    <div style="
                        background: white;
                        padding: 20px;
                        border-radius: 12px;
                        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
                        transition: 0.3s;
                    "
                    onmouseover="this.style.transform='scale(1.03)'"
                    onmouseout="this.style.transform='scale(1)'"
                    >
                        <h3>
                            <i class="fas fa-file-alt" style="color:#6c63ff;"></i>
                            {{ $doc['title'] }}
                        </h3>
                        <p style="color: #555;">{{ $doc['description'] }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <!-- ❌ No Results -->
            <div style="
                background: white;
                padding: 30px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            ">
                <h3>😢 No results found</h3>
                <p style="color: #777;">Try searching with different keywords.</p>
            </div>
        @endif

    </div>

    <!-- 🔹 Fake Loading Script -->
    <script>
        function fakeSearch() {
            document.getElementById("loading").style.display = "block";
            document.getElementById("results").style.display = "none";

            setTimeout(() => {
                document.getElementById("loading").style.display = "none";
                document.getElementById("results").style.display = "block";
            }, 1500);
        }
    </script>

</body>
</html>
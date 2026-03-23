<!DOCTYPE html>
<html>
<head>
    <title>Upload Document</title>
    <style>
        h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 24px;
}
    body {
        font-family: Arial;
        background-color: #f5f5f5;
    }

    form {
        width: 400px;
        margin: 50px auto;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    input, textarea, select {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: black;
        color: white;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background-color: #333;
    }
</style>
</head>
<body>

<h2>Upload Document</h2>

<form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
    @csrf   @csrf
    <input type="text" name="title" placeholder="Title">

    <input type="text" name="author" placeholder="Author">

    <textarea name="description" placeholder="Description"></textarea>

    <input type="text" name="category_id" placeholder="Category ID">

    <input type="file" name="file">

    <button type="submit">Upload</button>
    @if ($errors->any())
    <div style="color:red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
</form>
<script>
document.addEventListener("DOMContentLoaded", function() {

    const form = document.querySelector("form");
    const fileInput = document.querySelector('input[type="file"]');

    form.addEventListener("submit", function(e){

        const filePath = fileInput.value;

        // 1️⃣ Check if file is selected
        if(filePath === ""){
            alert("Please select a file to upload.");
            e.preventDefault();
            return;
        }

        // 2️⃣ Check file extension
        const allowedExtensions = /(\.pdf|\.doc|\.docx)$/i;
        if(!allowedExtensions.exec(filePath)){
            alert("Invalid file type. Only PDF or DOC/DOCX allowed.");
            fileInput.value = '';
            e.preventDefault();
            return;
        }

    });

});
</script>
</body>
</html>
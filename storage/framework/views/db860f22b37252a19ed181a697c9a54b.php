<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Text Preprocessing AI — SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/js/documents.js']); ?>
</head>
<body>

<nav class="nav">
    <a href="<?php echo e(route('documents.index')); ?>" class="nav-brand">smart<span>DMS</span></a>
    <div class="nav-right">
        <div class="user-badge" id="userBadge">
            <div class="user-avatar">
                <?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?>

            </div>
            <span class="user-name"><?php echo e(Auth::user()->name); ?></span>
            <div class="user-tooltip">
                <i class="fas fa-envelope" style="font-size:11px;"></i>
                <?php echo e(Auth::user()->email); ?>

            </div>
        </div>
        <a href="<?php echo e(route('documents.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus" style="font-size:12px;"></i> Upload New
        </a>
        <form method="POST" action="<?php echo e(route('logout')); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-ghost">
                <i class="fas fa-sign-out-alt" style="font-size:12px;"></i> Logout
            </button>
        </form>
    </div>
</nav>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-robot"></i> Text Preprocessing AI</h1>
        <p>Analyze text with tokenization, stopword removal, and lemmatization.</p>
    </div>

    <div class="container-fluid">
        <div class="row g-4">
            <!-- Input Section -->
            <div class="col-lg-6">
                <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h5 style="margin-bottom: 16px; font-weight: 600;"><i class="fas fa-pen"></i> Enter Text to Analyze</h5>
                    
                    <form id="preprocessForm">
                        <div class="mb-3">
                            <label for="textInput" class="form-label">Text Content</label>
                            <textarea class="form-control" id="textInput" rows="8" placeholder="Enter or paste your text here..." required style="font-size: 0.95rem; border-radius: 6px;"></textarea>
                            <small class="form-text text-muted d-block mt-2">The AI will tokenize, remove stopwords, and lemmatize your text.</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="removeStopwords" checked>
                                <label class="form-check-label" for="removeStopwords">
                                    Remove Stopwords (the, a, is, etc.)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="lemmatize" checked>
                                <label class="form-check-label" for="lemmatize">
                                    Lemmatize (running → run)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="padding: 10px; font-weight: 500;">
                            <i class="fas fa-magic"></i> Preprocess Text
                        </button>
                    </form>
                </div>

                <!-- Analyze Documents Section -->
                <?php if($documents && count($documents) > 0): ?>
                <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
                    <h5 style="margin-bottom: 16px; font-weight: 600;"><i class="fas fa-file-alt"></i> Analyze Your Documents</h5>
                    
                    <div class="list-group">
                        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" class="list-group-item list-group-item-action analyze-doc-btn" data-doc-id="<?php echo e($doc->document_id); ?>" style="text-align: left; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e9ecef;">
                                <h6 class="mb-1" style="font-weight: 600;"><?php echo e($doc->title); ?></h6>
                                <small class="text-muted"><?php echo e(Str::limit($doc->description, 60) ?: '—'); ?></small>
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Results Section -->
            <div class="col-lg-6">
                <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h5 style="margin-bottom: 20px; font-weight: 600;"><i class="fas fa-chart-bar"></i> Analysis Results</h5>

                    <!-- Loading -->
                    <div id="loadingSpinner" style="display: none; text-align: center; padding: 60px 20px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Processing text...</p>
                    </div>

                    <!-- Results -->
                    <div id="resultsContainer" style="display: none;">
                        <!-- Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div style="background: #f8f9fa; padding: 16px; border-radius: 6px;">
                                    <h6 style="font-size: 0.85rem; color: #666;">Original Length</h6>
                                    <p style="font-size: 1.8rem; font-weight: 600; margin: 8px 0; color: #333;" id="origLength">-</p>
                                    <small style="color: #999;">characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="background: #f8f9fa; padding: 16px; border-radius: 6px;">
                                    <h6 style="font-size: 0.85rem; color: #666;">Tokens Found</h6>
                                    <p style="font-size: 1.8rem; font-weight: 600; margin: 8px 0; color: #333;" id="tokenCount">-</p>
                                    <small style="color: #999;">processed words</small>
                                </div>
                            </div>
                        </div>

                        <!-- Cleaned Text -->
                        <div class="mb-4">
                            <h6 style="margin-bottom: 10px;"><i class="fas fa-broom"></i> Cleaned Text</h6>
                            <div style="background: #e7f3ff; padding: 12px; border-radius: 6px; border-left: 4px solid #0066cc; font-size: 0.95rem; color: #333; max-height: 120px; overflow-y: auto;" id="cleanedText">-</div>
                        </div>

                        <!-- Tokens -->
                        <div>
                            <h6 style="margin-bottom: 12px;"><i class="fas fa-tag"></i> Extracted Tokens</h6>
                            <div id="tokensList" style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; background: #f8f9fa; border-radius: 6px; min-height: 60px; align-content: flex-start;">
                                <!-- Tokens will be displayed here -->
                            </div>
                        </div>

                        <!-- Download Results -->
                        <div style="margin-top: 20px; display: flex; gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="downloadBtn">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copyTokensBtn">
                                <i class="fas fa-copy"></i> Copy Tokens
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="errorContainer" style="display: none;">
                        <div style="background: #fff3cd; padding: 16px; border-radius: 6px; border-left: 4px solid #ff6b6b;">
                            <h5 style="color: #cc0000; margin-bottom: 8px;">Error</h5>
                            <p style="color: #666;" id="errorMessage">-</p>
                        </div>
                    </div>

                    <!-- Default Message -->
                    <div id="defaultMessage" style="text-align: center; padding: 60px 20px; color: #999;">
                        <i class="fas fa-arrow-left fa-3x mb-3" style="opacity: 0.3; display: block;"></i>
                        <p>Enter text or select a document to analyze</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo e(asset('js/text-preprocessor.js')); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const preprocessor = new TextPreprocessor();
    let lastResults = null;

    // Form submission
    document.getElementById('preprocessForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const text = document.getElementById('textInput').value;
        const removeStopwords = document.getElementById('removeStopwords').checked;
        const lemmatize = document.getElementById('lemmatize').checked;

        if (!text.trim()) {
            alert('Please enter some text');
            return;
        }

        showLoading();

        const results = await preprocessor.preprocessText(text, {
            removeStopwords,
            lemmatize
        });

        displayResults(results);
    });

    // Analyze document buttons
    document.querySelectorAll('.analyze-doc-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const docId = this.dataset.docId;
            showLoading();

            const results = await preprocessor.analyzeDocument(docId);
            displayResults(results);
        });
    });

    // Download results
    document.getElementById('downloadBtn').addEventListener('click', function() {
        if (!lastResults) return;

        const data = {
            tokens: lastResults.tokens,
            token_count: lastResults.token_count,
            cleaned_text: lastResults.cleaned_text,
            original_length: lastResults.text_length,
            generated_at: new Date().toLocaleString()
        };

        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `preprocessing_results_${Date.now()}.json`;
        a.click();
    });

    // Copy tokens
    document.getElementById('copyTokensBtn').addEventListener('click', function() {
        if (!lastResults) return;

        const tokensText = lastResults.tokens.join(', ');
        navigator.clipboard.writeText(tokensText).then(() => {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        });
    });

    function showLoading() {
        document.getElementById('defaultMessage').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'none';
        document.getElementById('errorContainer').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    }

    function displayResults(results) {
        document.getElementById('loadingSpinner').style.display = 'none';

        if (!results.success) {
            document.getElementById('defaultMessage').style.display = 'none';
            document.getElementById('resultsContainer').style.display = 'none';
            document.getElementById('errorContainer').style.display = 'block';
            document.getElementById('errorMessage').textContent = results.error || 'An error occurred';
            return;
        }

        lastResults = results;
        document.getElementById('defaultMessage').style.display = 'none';
        document.getElementById('errorContainer').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'block';

        // Update statistics
        document.getElementById('origLength').textContent = results.text_length;
        document.getElementById('tokenCount').textContent = results.token_count;
        document.getElementById('cleanedText').textContent = results.cleaned_text;

        // Display tokens
        const tokensList = document.getElementById('tokensList');
        tokensList.innerHTML = results.tokens.map(token => 
            `<span style="display: inline-block; background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">${escapeHtml(token)}</span>`
        ).join('');
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>

</body>
</html>
<?php /**PATH C:\Smart-DMS\resources\views/documents/preprocess.blade.php ENDPATH**/ ?>
<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section" style="max-width: 800px; margin: 0 auto;">
    <div class="section-header">
        <h2>Add XML Data Source</h2>
    </div>

    <form action="/xml-sources/store" method="POST">
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>Source Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Central Bank Currency Rates" required>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>XML Feed URL</label>
            <input type="url" name="url" class="form-control" placeholder="https://example.com/feed.xml" required>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>Data Selector (Simple Path)</label>
            <input type="text" name="selector" class="form-control"
                placeholder="channel/item/title OR /root/element[@id='1']" required>
            <small style="color: var(--text-muted);">Use a path (e.g. <code>currency/usd</code>) or XPath if starting
                with /</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>Post Template</label>
            <textarea name="template" class="form-control" rows="3"
                placeholder="Today's USD Rate is: {value} TL #economy #currency" required></textarea>
            <small style="color: var(--text-muted);">Use <code>{value}</code> where you want the extracted data to
                appear.</small>
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label>Schedule Time (Daily)</label>
            <input type="time" name="schedule_time" class="form-control" value="09:00" required>
        </div>

        <div class="form-actions" style="text-align: right;">
            <a href="/xml-sources" class="btn btn-outline" style="margin-right: 1rem;">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Source</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
@php
    $issue = $newsletter ?? new \App\Models\BroadcastNewsletter();
@endphp
<div class="form-group">
    <label for="{{ $editorPrefix }}-issue-month">Issue month <span class="text-danger">*</span></label>
    <input id="{{ $editorPrefix }}-issue-month" type="month" name="issue_month"
        class="form-control" required
        value="{{ old('issue_month', optional($issue->issue_month)->format('Y-m') ?: now('America/Denver')->format('Y-m')) }}">
    <small class="form-text text-muted">One draft per month. Drafts may be saved for any month, but past-month issues cannot be scheduled. Nothing is sent unless you explicitly schedule it.</small>
</div>
<div class="form-group">
    <label for="{{ $editorPrefix }}-title">Newsletter title</label>
    <input id="{{ $editorPrefix }}-title" name="title" maxlength="180" class="form-control"
        placeholder="Research and monitoring update"
        value="{{ old('title', $issue->title) }}">
</div>
<div class="form-group">
    <label for="{{ $editorPrefix }}-body">Newsletter content</label>
    <textarea id="{{ $editorPrefix }}-body" name="body" rows="12" maxlength="30000"
        class="form-control js-broadcast-rich-editor"
        placeholder="Write here...">{{ old('body', $issue->body_format === 'html'
            ? $issue->body
            : app(\App\Services\BroadcastRichText::class)->render($issue->body, 'plain')) }}</textarea>
    <small class="form-text text-muted">
        Format with headings, colors, lists, tables, links and images by URL. Unsupported embeds are removed for email safety.
    </small>
</div>

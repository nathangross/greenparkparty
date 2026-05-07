<div class="space-y-3">
    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
        Save the update before previewing if you want recent form edits reflected here.
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <iframe
            title="Mailchimp email preview"
            srcdoc="{{ $html }}"
            class="w-full bg-white"
            style="height: 80vh;"
            sandbox
        ></iframe>
    </div>
</div>

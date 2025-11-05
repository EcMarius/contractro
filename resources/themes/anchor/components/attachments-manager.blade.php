<!-- Attachments Upload Component for Contracts -->
<div x-data="attachmentManager()" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Atașamente</h3>
        <button type="button"
                @click="$refs.fileInput.click()"
                class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Adaugă Fișier
        </button>
        <input type="file"
               x-ref="fileInput"
               @change="handleFileSelect"
               multiple
               class="hidden"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
    </div>

    <!-- Upload Progress -->
    <div x-show="uploading" class="mb-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400 mr-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-blue-800 dark:text-blue-300">Se încarcă fișierul...</span>
            </div>
            <div class="mt-2 w-full bg-blue-200 dark:bg-blue-700 rounded-full h-2">
                <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-300"
                     :style="`width: ${uploadProgress}%`"></div>
            </div>
        </div>
    </div>

    <!-- Attachments List -->
    <div class="space-y-2">
        <template x-for="(attachment, index) in attachments" :key="attachment.id">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <div class="flex items-center flex-1 min-w-0">
                    <!-- File Icon -->
                    <div class="flex-shrink-0 mr-3">
                        <template x-if="getFileIcon(attachment.file_type) === 'pdf'">
                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 18h12V6h-4V2H4v16zm-2 1V0h10l4 4v16H2v-1z"/>
                            </svg>
                        </template>
                        <template x-if="getFileIcon(attachment.file_type) === 'doc'">
                            <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 18h12V6h-4V2H4v16zm-2 1V0h10l4 4v16H2v-1z"/>
                            </svg>
                        </template>
                        <template x-if="getFileIcon(attachment.file_type) === 'excel'">
                            <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 18h12V6h-4V2H4v16zm-2 1V0h10l4 4v16H2v-1z"/>
                            </svg>
                        </template>
                        <template x-if="getFileIcon(attachment.file_type) === 'image'">
                            <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                        </template>
                        <template x-if="getFileIcon(attachment.file_type) === 'zip'">
                            <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 18h12V6h-4V2H4v16zm-2 1V0h10l4 4v16H2v-1z"/>
                            </svg>
                        </template>
                        <template x-if="!['pdf','doc','excel','image','zip'].includes(getFileIcon(attachment.file_type))">
                            <svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                        </template>
                    </div>

                    <!-- File Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="attachment.file_name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="formatFileSize(attachment.size)"></span>
                            <span class="mx-1">•</span>
                            <span x-text="formatDate(attachment.uploaded_at)"></span>
                            <span class="mx-1" x-show="attachment.uploaded_by_name">•</span>
                            <span x-show="attachment.uploaded_by_name" x-text="attachment.uploaded_by_name"></span>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-4">
                    <a :href="`/contracts/${contractId}/attachments/${attachment.id}/download`"
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                       title="Descarcă">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </a>
                    <button type="button"
                            @click="deleteAttachment(attachment.id)"
                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                            title="Șterge">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="attachments.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Niciun atașament</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Click pe "Adaugă Fișier" pentru a atașa documente
            </p>
        </div>
    </div>

    <!-- File Type Info -->
    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <p class="text-xs text-gray-600 dark:text-gray-400">
            <strong>Tipuri acceptate:</strong> PDF, Word (.doc, .docx), Excel (.xls, .xlsx), Imagini (.jpg, .png), Arhive (.zip)
            <br>
            <strong>Dimensiune maximă:</strong> 10 MB per fișier
        </p>
    </div>
</div>

<script>
function attachmentManager() {
    return {
        contractId: @js($contractId ?? 0),
        attachments: @js($attachments ?? []),
        uploading: false,
        uploadProgress: 0,

        async handleFileSelect(event) {
            const files = Array.from(event.target.files);

            for (const file of files) {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert(`Fișierul "${file.name}" depășește limita de 10 MB.`);
                    continue;
                }

                await this.uploadFile(file);
            }

            // Reset input
            event.target.value = '';
        },

        async uploadFile(file) {
            this.uploading = true;
            this.uploadProgress = 0;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('contract_id', this.contractId);

            try {
                const response = await fetch(`/contracts/${this.contractId}/attachments`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    // Track upload progress
                    onUploadProgress: (progressEvent) => {
                        this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.attachments.push(data.attachment);
                } else {
                    alert('Eroare la încărcarea fișierului. Vă rugăm încercați din nou.');
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Eroare la încărcarea fișierului. Vă rugăm încercați din nou.');
            } finally {
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },

        async deleteAttachment(attachmentId) {
            if (!confirm('Sigur doriți să ștergeți acest atașament?')) {
                return;
            }

            try {
                const response = await fetch(`/contracts/${this.contractId}/attachments/${attachmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    this.attachments = this.attachments.filter(a => a.id !== attachmentId);
                } else {
                    alert('Eroare la ștergerea atașamentului.');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Eroare la ștergerea atașamentului.');
            }
        },

        getFileIcon(fileType) {
            if (fileType.includes('pdf')) return 'pdf';
            if (fileType.includes('word') || fileType.includes('doc')) return 'doc';
            if (fileType.includes('excel') || fileType.includes('sheet')) return 'excel';
            if (fileType.includes('image')) return 'image';
            if (fileType.includes('zip') || fileType.includes('archive')) return 'zip';
            return 'file';
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    }
}
</script>

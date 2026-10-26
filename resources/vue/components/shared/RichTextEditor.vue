<template>
    <textarea ref="input" :id="id" :value="value"></textarea>
</template>

<script>
import { Jodit } from 'jodit';
import 'jodit/es2021/jodit.min.css';

export default {
    props: {
        id: String,
        value: { type: String, default: '' },
        init: { type: Object, default: () => ({}) }
    },
    mounted() {
        this.editor = Jodit.make(this.$refs.input, {
            language: 'es',
            height: this.init.height || 300,
            minHeight: 100,
            statusbar: false,
            toolbarAdaptive: false,
            buttons: 'undo,redo,|,paragraph,bold,italic,brush,|,align,ul,ol,outdent,indent,|,table,link,image,hr,eraser,|,source,fullsize,preview,print',
            enableDragAndDropFileToEditor: false,
            uploader: { insertImageAsBase64URI: false },
            sourceEditor: 'area',
            useAceEditor: false,
            beautifyHTML: false,
            cleanHTML: { removeOnError: true, safeJavaScriptLink: true }
        });
        this.editor.value = this.value || '';
        // Loading a saved description must never rewrite the form's value.
        this.dirty = false;
        this.editor.container.addEventListener('input', this.markDirty, true);
        this.editor.events.on('afterCommand', this.afterCommand);
        this.editor.events.on('change', this.onChange);
        this.form = this.$refs.input.closest('form');
        if (this.form) this.form.addEventListener('submit', this.flush, true);
    },
    beforeDestroy() {
        if (this.editor) {
            if (this.form) this.form.removeEventListener('submit', this.flush, true);
            this.editor.container.removeEventListener('input', this.markDirty, true);
            this.editor.events.off('afterCommand', this.afterCommand);
            this.editor.events.off('change', this.onChange);
            this.editor.destruct();
        }
    },
    watch: {
        value(value) {
            if (this.editor && this.editor.value !== (value || '')) {
                this.updating = true;
                this.editor.value = value || '';
                this.updating = false;
                this.dirty = false;
            }
        }
    },
    methods: {
        markDirty() {
            if (!this.updating) this.dirty = true;
        },
        afterCommand() {
            this.markDirty();
            this.flush();
        },
        flush() {
            // Submit can precede the editor's debounced change event.
            if (this.editor && this.dirty) this.onChange(this.editor.value);
        },
        onChange(value) {
            if (this.dirty && !this.updating && value !== this.value) this.$emit('input', value);
        }
    }
};
</script>

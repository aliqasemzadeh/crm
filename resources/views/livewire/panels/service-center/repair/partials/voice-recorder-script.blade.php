@once
<script>
    window.repairVoiceRecorder = function (field) {
        return {
            field: field,
            recording: false,
            mediaRecorder: null,
            chunks: [],
            async toggle() {
                if (this.recording) {
                    this.stop();
                    return;
                }
                await this.start();
            },
            async start() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.chunks = [];
                    this.mediaRecorder = new MediaRecorder(stream);
                    this.mediaRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) this.chunks.push(e.data);
                    };
                    this.mediaRecorder.onstop = async () => {
                        stream.getTracks().forEach((t) => t.stop());
                        const blob = new Blob(this.chunks, { type: 'audio/webm' });
                        const file = new File([blob], `voice-${Date.now()}.webm`, { type: 'audio/webm' });
                        await this.$wire.set('voiceTargetField', this.field);

                        if (typeof this.$wire.$upload === 'function') {
                            await this.$wire.$upload('voiceAudio', file);
                        } else {
                            await new Promise((resolve, reject) => {
                                this.$wire.upload('voiceAudio', file, () => resolve(), () => reject(), () => {});
                            });
                        }

                        await this.$wire.transcribeAudio(this.field);
                    };
                    this.mediaRecorder.start();
                    this.recording = true;
                } catch (e) {
                    console.error(e);
                    alert(@js(__('app.repair_microphone_denied')));
                }
            },
            stop() {
                if (this.mediaRecorder && this.recording) {
                    this.mediaRecorder.stop();
                    this.recording = false;
                }
            },
        };
    };
</script>
@endonce

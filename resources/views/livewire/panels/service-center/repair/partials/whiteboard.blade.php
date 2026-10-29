<div
    x-data="{
        isOpen: false,
        drawing: false,
        ctx: null,
        color: '#111827',
        size: 3,
        lastX: 0,
        lastY: 0,
        openBoard() { this.isOpen = true; this.$nextTick(() => this.initCanvas()); },
        closeBoard() { this.isOpen = false; },
        clearBoard() { const c = this.$refs.board; this.ctx.clearRect(0,0,c.width,c.height); },
        saveBoard() {
            const c = this.$refs.board;
            const data = c.toDataURL('image/png');
            this.$wire.set('device_problem_file', data);
            this.isOpen = false;
        },
        initCanvas() {
            const canvas = this.$refs.board;
            const parent = canvas.parentElement;
            const dpr = window.devicePixelRatio || 1;
            const rect = parent.getBoundingClientRect();
            canvas.width = Math.floor(rect.width * dpr);
            canvas.height = Math.floor((rect.height - 48) * dpr);
            canvas.style.width = rect.width + 'px';
            canvas.style.height = (rect.height - 48) + 'px';
            this.ctx = canvas.getContext('2d');
            this.ctx.scale(dpr, dpr);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.size;
        },
        pointerDown(e) {
            this.drawing = true;
            const p = this.point(e);
            this.lastX = p.x; this.lastY = p.y;
        },
        pointerMove(e) {
            if (!this.drawing) { return; }
            const p = this.point(e);
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.size;
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
            this.lastX = p.x; this.lastY = p.y;
        },
        pointerUp() { this.drawing = false; },
        point(e) {
            const canvas = this.$refs.board;
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches ? e.touches[0] : null;
            const clientX = touch ? touch.clientX : e.clientX;
            const clientY = touch ? touch.clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }
    }"
    x-cloak
    class="relative"
>
    <flux:tooltip content="{{ __('app.open_whiteboard') }}">
        <flux:button type="button" size="sm" variant="subtle" icon="pencil-square" @click="openBoard()" />
    </flux:tooltip>

    <div x-show="isOpen" wire:ignore class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="flex h-[85vh] w-full max-w-3xl flex-col rounded-lg bg-white p-3 shadow-xl dark:bg-neutral-900">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:label class="!mb-0">{{ __('app.color') }}</flux:label>
                    <input type="color" x-model="color" class="h-6 w-10 cursor-pointer appearance-none border-0 bg-transparent p-0" />
                    <flux:label class="!mb-0">{{ __('app.thickness') }}</flux:label>
                    <input type="range" min="1" max="20" x-model.number="size" class="w-24 sm:w-32" />
                </div>
                <div class="flex items-center gap-2">
                    <flux:button type="button" size="xs" variant="filled" @click="clearBoard()">{{ __('app.clear') }}</flux:button>
                    <flux:button type="button" size="xs" variant="primary" @click="saveBoard()">{{ __('app.save') }}</flux:button>
                    <flux:button type="button" size="xs" variant="ghost" @click="closeBoard()">{{ __('app.close') }}</flux:button>
                </div>
            </div>
            <div class="min-h-0 flex-1 w-full overflow-hidden rounded border border-black/10 dark:border-white/10">
                <canvas
                    x-ref="board"
                    class="h-full w-full touch-none"
                    @mousedown.prevent="pointerDown($event)"
                    @mousemove.prevent="pointerMove($event)"
                    @mouseup.prevent="pointerUp()"
                    @mouseleave.prevent="pointerUp()"
                    @touchstart.passive="pointerDown($event)"
                    @touchmove.passive="pointerMove($event)"
                    @touchend.passive="pointerUp()"
                ></canvas>
            </div>
        </div>
    </div>
</div>

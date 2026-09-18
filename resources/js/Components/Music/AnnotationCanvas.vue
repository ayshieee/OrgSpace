<script setup>
// Freehand pen/highlighter/eraser drawing over a fixed-size surface. Strokes
// are stored as normalized 0-1 points so they stay aligned regardless of the
// rendered canvas size — the parent persists `strokes` to the server as-is.
import { onMounted, ref, watch } from 'vue';

const ERASE_RADIUS_PX = 12;

function makeStrokeId() {
    return 'stroke-' + Math.random().toString(36).slice(2, 10);
}

function toPixels(points, width, height) {
    return points.map((p) => ({ x: p.x * width, y: p.y * height }));
}

function drawStroke(ctx, stroke, width, height) {
    const pts = toPixels(stroke.points, width, height);
    if (pts.length === 0) return;
    ctx.beginPath();
    ctx.moveTo(pts[0].x, pts[0].y);
    for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = stroke.color;
    if (stroke.tool === 'highlight') {
        ctx.globalAlpha = 0.35;
        ctx.lineWidth = 16;
    } else {
        ctx.globalAlpha = 1;
        ctx.lineWidth = 2.5;
    }
    ctx.stroke();
    ctx.globalAlpha = 1;
}

/** Distance from a point to the nearest segment of a stroke, in pixel space. */
function distanceToStroke(x, y, stroke, width, height) {
    const pts = toPixels(stroke.points, width, height);
    let min = Infinity;
    for (let i = 0; i < pts.length - 1; i++) {
        const a = pts[i];
        const b = pts[i + 1];
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        const lenSq = dx * dx + dy * dy;
        let t = lenSq === 0 ? 0 : ((x - a.x) * dx + (y - a.y) * dy) / lenSq;
        t = Math.max(0, Math.min(1, t));
        const px = a.x + t * dx;
        const py = a.y + t * dy;
        min = Math.min(min, Math.hypot(x - px, y - py));
    }
    return pts.length === 1 ? Math.hypot(x - pts[0].x, y - pts[0].y) : min;
}

const props = defineProps({
    width: { type: Number, required: true },
    height: { type: Number, required: true },
    strokes: { type: Array, required: true },
    tool: { type: String, default: null },
    color: { type: String, required: true },
    editable: { type: Boolean, default: false },
});
const emit = defineEmits(['addStroke', 'eraseStroke']);

const canvasRef = ref(null);

let drawingPoints = null;
let erasedIds = new Set();

function redraw() {
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    ctx.clearRect(0, 0, props.width, props.height);
    props.strokes.forEach((s) => drawStroke(ctx, s, props.width, props.height));
}

onMounted(redraw);
watch(() => [props.strokes, props.width, props.height], redraw);

function toLocalPoint(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    const px = ((e.clientX - rect.left) / rect.width) * props.width;
    const py = ((e.clientY - rect.top) / rect.height) * props.height;
    return { px, py, nx: px / props.width, ny: py / props.height };
}

function eraseAt(px, py) {
    for (const s of props.strokes) {
        if (erasedIds.has(s.id)) continue;
        if (distanceToStroke(px, py, s, props.width, props.height) <= ERASE_RADIUS_PX) {
            erasedIds.add(s.id);
            emit('eraseStroke', s.id);
        }
    }
}

function handlePointerDown(e) {
    if (!props.editable || !props.tool) return;
    e.currentTarget.setPointerCapture(e.pointerId);
    const { px, py, nx, ny } = toLocalPoint(e);
    if (props.tool === 'eraser') {
        erasedIds = new Set();
        eraseAt(px, py);
        return;
    }
    drawingPoints = [{ x: nx, y: ny }];
}

function handlePointerMove(e) {
    if (!props.editable || !props.tool) return;
    const { px, py, nx, ny } = toLocalPoint(e);
    if (props.tool === 'eraser') {
        if (e.buttons === 1) eraseAt(px, py);
        return;
    }
    if (!drawingPoints) return;
    drawingPoints.push({ x: nx, y: ny });
    const ctx = canvasRef.value?.getContext('2d');
    const pts = drawingPoints;
    if (ctx && pts.length >= 2) {
        const a = pts[pts.length - 2];
        const b = pts[pts.length - 1];
        ctx.beginPath();
        ctx.moveTo(a.x * props.width, a.y * props.height);
        ctx.lineTo(b.x * props.width, b.y * props.height);
        ctx.lineCap = 'round';
        ctx.strokeStyle = props.color;
        if (props.tool === 'highlight') {
            ctx.globalAlpha = 0.35;
            ctx.lineWidth = 16;
        } else {
            ctx.globalAlpha = 1;
            ctx.lineWidth = 2.5;
        }
        ctx.stroke();
        ctx.globalAlpha = 1;
    }
}

function handlePointerUp() {
    if (props.tool === 'eraser') return;
    const pts = drawingPoints;
    drawingPoints = null;
    if (!pts || pts.length < 2 || !props.tool) return;
    emit('addStroke', { id: makeStrokeId(), tool: props.tool, color: props.color, points: pts });
}
</script>

<template>
    <canvas
        ref="canvasRef"
        :width="width"
        :height="height"
        class="absolute inset-0 w-full h-full"
        :style="{
            touchAction: editable && tool ? 'none' : 'auto',
            cursor: editable && tool ? 'crosshair' : 'default',
            pointerEvents: editable && tool ? 'auto' : 'none',
        }"
        @pointerdown="handlePointerDown"
        @pointermove="handlePointerMove"
        @pointerup="handlePointerUp"
    />
</template>

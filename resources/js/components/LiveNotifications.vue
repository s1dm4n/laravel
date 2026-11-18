<template>
    <aside v-if="article" class="live-notice" role="status" aria-live="polite">
        <div>Новая публикация</div>
        <a :href="article.url">{{ article.name }}</a>
        <button type="button" aria-label="Закрыть уведомление" @click="article = null">×</button>
    </aside>
</template>
<script>
export default {
    data() { return { article: null }; },
    mounted() {
        window.Echo.channel('articles').listen('NewArticleEvent', (event) => {
            this.article = event.article;
        });
    },
    beforeUnmount() { window.Echo.leaveChannel('articles'); },
};
</script>
<style>
.live-notice { position: fixed; right: 24px; bottom: 24px; z-index: 100; max-width: min(400px, calc(100vw - 48px)); padding: 24px 48px 24px 24px; background: white; border: 1px solid #cbdafa; border-radius: 12px; box-shadow: 0 12px 40px #172b4630; }
.live-notice div { color: #53677f; margin-bottom: 8px; }
.live-notice a { font-weight: 700; }
.live-notice button { position: absolute; right: 8px; top: 8px; border: 0; background: none; font-size: 24px; cursor: pointer; }
</style>

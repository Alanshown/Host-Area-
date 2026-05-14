<template>
  <div class="hall-page" :class="hallPageClass" :style="hallPageStyle">
    <div v-if="!auth.isLoggedIn.value" class="hall-page__auth-state">
      <div class="hall-page__auth-card">
        <h1 class="hall-page__auth-title">登录后进入社区聊天大厅</h1>
        <p class="hall-page__auth-text">进入独立聊天页面，与在线成员实时交流，支持附件、表情和机器人互动。</p>
        <div class="hall-page__auth-actions">
          <NuxtLink to="/login" class="hall-action-btn hall-action-btn--primary">前往登录</NuxtLink>
          <NuxtLink to="/register" class="hall-action-btn hall-action-btn--ghost">注册账号</NuxtLink>
        </div>
      </div>
    </div>

    <div v-else class="hall-layout">
      <header class="hall-topbar">
        <div class="hall-topbar__main">
          <NuxtLink to="/" class="hall-topbar__back">返回社区</NuxtLink>
          <div class="hall-topbar__channel">
            <div class="hall-topbar__badge">#</div>
            <div class="hall-topbar__info">
              <h1 class="hall-topbar__title">{{ currentChannelMeta.title }}</h1>
              <div class="hall-topbar__presence-wrap">
                <p class="hall-topbar__presence">
                  <span>{{ onlineCount }} 人在线</span>
                </p>
                <div class="hall-typing-indicator" :class="{ 'is-active': activeTypingUsers.length > 0 }">
                  <div class="hall-typing-avatars">
                    <template v-for="user in activeTypingUsers.slice(0, 3)" :key="user.id">
                      <img v-if="memberAvatarUrl(user)" :src="memberAvatarUrl(user)" class="hall-typing-avatar" />
                      <span v-else class="hall-typing-avatar hall-typing-avatar--fallback">{{ memberInitial(user) }}</span>
                    </template>
                  </div>
                  <span class="hall-typing-text">{{ typingSummary }}</span>
                  <div class="hall-typing-dots">
                    <span></span><span></span><span></span>
                  </div>
                </div>
              </div>
              <div class="hall-topbar__tabs" aria-label="频道切换">
                <NuxtLink
                  v-for="item in channelTabs"
                  :key="item.slug"
                  :to="item.slug === 'public-lobby' ? '/channel' : `/channel/${item.slug}`"
                  class="hall-topbar__tab"
                  :class="{ 'hall-topbar__tab--active': item.slug === currentChannel }"
                >
                  {{ item.shortTitle }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <div class="hall-topbar__side">
          <div v-if="visibleMembers.length" class="hall-topbar__crowd" aria-label="在线成员预览">
            <NuxtLink
              v-for="member in visibleMembers"
              :key="member.id"
              :to="`/user/${member.id}`"
              class="hall-crowd-avatar"
              :title="member.is_typing ? `${member.username} 正在输入` : member.username"
            >
              <img v-if="memberAvatarUrl(member)" :src="memberAvatarUrl(member)" :alt="member.username" class="hall-crowd-avatar__img" />
              <span v-else class="hall-crowd-avatar__img hall-crowd-avatar__img--fallback" :class="member.role === 'admin' ? 'hall-crowd-avatar__img--admin' : ''">{{ memberInitial(member) }}</span>
              <span class="hall-crowd-avatar__dot" :class="member.is_typing ? 'hall-crowd-avatar__dot--typing' : ''"></span>
            </NuxtLink>
              <span
                v-if="hasOverflowMembers"
                class="hall-crowd-more"
                :title="`还有 ${crowdOverflowCount} 位在线成员`"
                :aria-label="`还有 ${crowdOverflowCount} 位在线成员`"
              >
                <span></span>
                <span></span>
                <span></span>
              </span>
          </div>
          <button type="button" class="hall-topbar__settings" @click="openSettingsModal">
            <span class="hall-topbar__settings-icon">⚙</span>
            <span>大厅设置</span>
          </button>
        </div>
      </header>

      <Transition name="hall-settings">
        <div v-if="settingsOpen" class="hall-settings-layer" @click.self="closeSettingsModal">
          <section class="hall-settings-dialog">
            <header class="hall-settings__header">
              <div>
                <p class="hall-settings__eyebrow">Channel Control</p>
                <h2 class="hall-settings__title">{{ currentChannelMeta.title }} 设置</h2>
                <p class="hall-settings__subtitle">{{ currentChannelMeta.description }}</p>
              </div>
              <button type="button" class="hall-settings__close" @click="closeSettingsModal">关闭</button>
            </header>

            <div class="hall-settings__body">
              <section class="hall-settings__panel">
                <div class="hall-settings__panel-head">
                  <h3>主题外观</h3>
                  <p>切换经典、Claude 风格或自定义背景模式。</p>
                </div>
                <div class="hall-theme-grid">
                  <button type="button" class="hall-theme-card" :class="{ 'is-active': settingsForm.themeVariant === 'classic' }" @click="settingsForm.themeVariant = 'classic'">
                    <strong>经典大厅</strong>
                    <span>保留当前玻璃感亮色聊天室。</span>
                  </button>
                  <button type="button" class="hall-theme-card hall-theme-card--claude" :class="{ 'is-active': settingsForm.themeVariant === 'claude' }" @click="settingsForm.themeVariant = 'claude'">
                    <strong>Claude 风格</strong>
                    <span>暖米色纸张质感，适合长文和思考对话。</span>
                  </button>
                  <button type="button" class="hall-theme-card hall-theme-card--custom" :class="{ 'is-active': settingsForm.themeVariant === 'custom' }" @click="settingsForm.themeVariant = 'custom'">
                    <strong>自定义背景</strong>
                    <span>上传图片作为全页背景，消息卡片维持默认亮色。</span>
                  </button>
                </div>

                <div class="hall-settings__switches">
                  <label class="hall-switch-row">
                    <span>
                      <strong>屏蔽 Alma 消息</strong>
                      <small>仅隐藏机器人消息，不影响 @ 提及和在线状态。</small>
                    </span>
                    <input v-model="settingsForm.hideBot" type="checkbox" class="hall-switch-row__control" />
                  </label>
                </div>

                <div v-if="settingsForm.themeVariant === 'custom'" class="hall-custom-bg-panel">
                  <div class="hall-custom-bg-panel__info">
                    <strong>背景图</strong>
                    <span>建议上传 16:9 或更宽的图片，页面会自动加透明壳层。</span>
                  </div>
                  <label class="hall-custom-bg-panel__upload">
                    <input type="file" accept="image/*" class="hidden" @change="handleCustomBackgroundSelected" />
                    <span>{{ customBackgroundFile ? customBackgroundFile.name : '选择背景图片' }}</span>
                  </label>
                  <button v-if="settingsForm.customBackgroundPath || customBackgroundFile" type="button" class="hall-custom-bg-panel__clear" @click="clearCustomBackground">移除背景</button>
                </div>
              </section>

              <section v-if="auth.user.value?.role === 'admin'" class="hall-settings__panel">
                <div class="hall-settings__panel-head">
                  <h3>管理员禁言</h3>
                  <p>仅当前频道生效，用户仍可浏览消息但无法发送。</p>
                </div>

                <div class="hall-mute-toolbar">
                  <label>
                    <span>禁言时长</span>
                    <select v-model="muteDurationMinutes" class="hall-settings__select">
                      <option :value="10">10 分钟</option>
                      <option :value="30">30 分钟</option>
                      <option :value="60">1 小时</option>
                      <option :value="180">3 小时</option>
                      <option :value="1440">24 小时</option>
                    </select>
                  </label>
                </div>

                <div class="hall-mute-members">
                  <button
                    v-for="member in muteCandidates"
                    :key="member.id"
                    type="button"
                    class="hall-mute-member"
                    :disabled="muteSubmittingUserId === member.id"
                    @click="muteMember(member.id)"
                  >
                    <img v-if="memberAvatarUrl(member)" :src="memberAvatarUrl(member)" :alt="member.username" class="hall-mute-member__avatar" />
                    <span v-else class="hall-mute-member__avatar hall-mute-member__avatar--fallback">{{ memberInitial(member) }}</span>
                    <span class="hall-mute-member__meta">
                      <strong>{{ member.username }}</strong>
                      <small>{{ member.role === 'admin' ? '管理员' : '在线成员' }}</small>
                    </span>
                  </button>
                </div>

                <div class="hall-mute-list">
                  <article v-for="mute in activeMutes" :key="`${mute.user_id}-${mute.muted_until}`" class="hall-mute-card">
                    <img v-if="resolveMediaUrl(mute.avatar)" :src="resolveMediaUrl(mute.avatar)" :alt="mute.username" class="hall-mute-card__avatar" />
                    <span v-else class="hall-mute-card__avatar hall-mute-card__avatar--fallback">{{ mute.username?.[0]?.toUpperCase() || '?' }}</span>
                    <div class="hall-mute-card__meta">
                      <strong>{{ mute.username }}</strong>
                      <small>截止 {{ formatMuteTime(mute.muted_until) }} · 操作者 {{ mute.muted_by || '管理员' }}</small>
                    </div>
                    <button type="button" class="hall-mute-card__remove" @click="unmuteMember(mute.user_id)">解除</button>
                  </article>
                  <p v-if="!activeMutes.length" class="hall-mute-list__empty">当前频道还没有禁言记录。</p>
                </div>

                <div class="hall-command-preview">
                  <div class="hall-settings__panel-head hall-settings__panel-head--compact">
                    <div>
                      <h3>操作预览</h3>
                      <p>点击管理员指令，查看可直接发给 Alma 的敏感管理命令示例。</p>
                    </div>
                    <div class="hall-command-preview__tabs">
                      <button
                        type="button"
                        class="hall-command-preview__tab"
                        :class="{ 'is-active': settingsPreviewMode === 'admin-commands' }"
                        @click="settingsPreviewMode = 'admin-commands'"
                      >管理员指令</button>
                    </div>
                  </div>

                  <div v-if="settingsPreviewMode === 'admin-commands'" class="hall-command-preview__list">
                    <article v-for="item in adminCommandPreviews" :key="item.command" class="hall-command-card">
                      <div class="hall-command-card__meta">
                        <strong>{{ item.title }}</strong>
                        <small>{{ item.description }}</small>
                      </div>
                      <pre class="hall-command-card__code">{{ item.command }}</pre>
                      <button
                        type="button"
                        class="hall-command-card__copy"
                        @click="copyAdminCommand(item.command)"
                      >{{ copiedAdminCommand === item.command ? '已复制' : '复制' }}</button>
                    </article>
                  </div>
                </div>
              </section>
            </div>

            <footer class="hall-settings__footer">
              <p v-if="settingsError" class="hall-settings__error">{{ settingsError }}</p>
              <button type="button" class="hall-settings__ghost" @click="closeSettingsModal">取消</button>
              <button type="button" class="hall-settings__save" :disabled="settingsSaving" @click="saveSettings">{{ settingsSaving ? '保存中...' : '保存设置' }}</button>
            </footer>
          </section>
        </div>
      </Transition>

      <main class="hall-main">
        <section class="hall-feed">
          <div ref="messageViewportRef" class="hall-feed__scroll">
            <!-- 加载状态 -->
            <div v-if="loading" class="hall-loading-overlay">
              <div class="hall-loading-spinner">
                <div class="hall-loading-ring"></div>
                <div class="hall-loading-ring hall-loading-ring--2"></div>
                <div class="hall-loading-ring hall-loading-ring--3"></div>
                <div class="hall-loading-dot"></div>
              </div>
              <p class="hall-loading-text">正在加载大厅消息...</p>
            </div>

            <!-- 错误状态 -->
            <div v-else-if="loadError" class="hall-state hall-state--error">
              <div class="hall-state__stack">
                <p>{{ loadError }}</p>
                <button type="button" class="hall-state__retry" @click="retryBootstrap">重新连接大厅</button>
              </div>
            </div>

            <!-- 空状态 -->
            <div v-else-if="!messages.length" class="hall-state">大厅里还没有消息，发一条开场吧。</div>

            <!-- 消息列表 -->
            <div v-else class="hall-message-list">
              <!-- System events: bans & recalls -->
              <template v-for="item in mergedTimeline" :key="item.sortKey">
                <!-- Recall record -->
                <div v-if="item.type === 'recall'" class="hall-system-event">
                  <div class="hall-system-event__pill">
                    <span>{{ item.recalled_by_name === auth.user.value?.username ? '你' : item.recalled_by_name }} 撤回了成员 <strong>{{ item.original_author_name }}</strong> 的一条消息</span>
                  </div>
                  <time class="hall-system-event__time">{{ formatMessageTime(item.created_at) }}</time>
                </div>

                <!-- System ban record -->
                <div v-else-if="item.type === 'system' && (item.message_type === 'system_ban')" class="hall-system-event hall-system-event--ban">
                  <div class="hall-system-event__pill hall-system-event__pill--ban">
                    <span>{{ item.content }}</span>
                  </div>
                  <time class="hall-system-event__time">{{ formatMessageTime(item.created_at) }}</time>
                </div>

                <!-- System notice record -->
                <div v-else-if="item.type === 'system' && (item.message_type === 'system_notice')" class="hall-system-event">
                  <div class="hall-system-event__pill">
                    <span>{{ item.content }}</span>
                  </div>
                  <time class="hall-system-event__time">{{ formatMessageTime(item.created_at) }}</time>
                </div>

                <!-- Normal message -->
                <article
                  v-else
                  class="hall-message"
                  :class="{
                    'hall-message--self': isOwnMessage(item),
                    'hall-message--bot': item.author_role === 'bot',
                    'hall-message--pending': item.isPending,
                    'hall-message--failed': item.isFailed,
                  }"
                  @mouseenter="hoveredMessageId = item.id"
                  @mouseleave="hoveredMessageId = null"
                >
                <NuxtLink
                  v-if="item.user?.id && !isOwnMessage(item)"
                  :to="`/user/${item.user.id}`"
                  class="hall-message__avatar-link"
                >
                  <img
                    v-if="messageAvatarUrl(item)"
                    :src="messageAvatarUrl(item)"
                    alt="message avatar"
                    class="hall-message__avatar"
                  />
                  <div v-else class="hall-message__avatar hall-message__avatar--fallback" :class="avatarToneClass(item)">
                    {{ messageInitial(item) }}
                  </div>
                </NuxtLink>

                <img
                  v-else-if="!isOwnMessage(item) && messageAvatarUrl(item)"
                  :src="messageAvatarUrl(item)"
                  alt="message avatar"
                  class="hall-message__avatar"
                  @contextmenu.prevent="item.author_role === 'bot' && isAdmin ? openBotAvatarMenu($event) : null"
                />

                <div
                  v-else-if="!isOwnMessage(item)"
                  class="hall-message__avatar hall-message__avatar--fallback"
                  :class="avatarToneClass(item)"
                >
                  {{ messageInitial(item) }}
                </div>

                <div class="hall-message__body">
                  <div class="hall-message__meta">
                    <NuxtLink
                      v-if="item.user?.id"
                      :to="`/user/${item.user.id}`"
                      class="hall-message__author"
                    >
                      {{ item.author_name }}
                    </NuxtLink>
                    <span v-else class="hall-message__author">{{ item.author_name }}</span>
                    <span v-if="item.author_role === 'admin'" class="hall-message__role">ADMIN</span>
                    <span v-if="item.author_role === 'bot'" class="hall-message__role hall-message__role--bot">Bot</span>
                    <time class="hall-message__time">{{ formatMessageTime(item.created_at) }}</time>
                    <!-- Recall button -->
                    <Transition name="fade">
                      <button
                        v-if="hoveredMessageId === item.id && canRecall(item)"
                        type="button"
                        class="hall-message__recall"
                        :disabled="recallingId === item.id"
                        @click="recallMessage(item)"
                      >
                        {{ recallingId === item.id ? '撤回中...' : '撤回' }}
                      </button>
                    </Transition>
                  </div>

                  <div class="hall-bubble">
                    <!-- TTS button for bot messages (shown after content is loaded) -->
                    <div v-if="item.author_role === 'bot' && item.content && !item.isStreaming" class="hall-bubble__tts-wrap">
                      <button
                        type="button"
                        class="hall-bubble__tts-btn"
                        :class="{ 'is-speaking': currentSpeakingMessageId === item.id }"
                        :title="currentSpeakingMessageId === item.id ? '停止朗读' : '朗读消息'"
                        @click="toggleMessageTTS(item.id, item.content)"
                      >
                        <svg v-if="currentSpeakingMessageId !== item.id" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                          <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                          <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                          <line x1="23" y1="9" x2="17" y2="15"></line>
                          <line x1="17" y1="9" x2="23" y2="15"></line>
                        </svg>
                      </button>
                    </div>

                    <div v-if="item.content" class="hall-bubble__content">
                      <ChatMessageRenderer :content="item.content" :is-bot="item.author_role === 'bot'" :message-id="item.id" :is-streaming="item.isStreaming ?? false" />
                    </div>

                    <!-- 工具调用可视化（实时展示） -->
                    <div v-if="item.author_role === 'bot' && item.meta?.toolCalls?.length" class="hall-tool-viz">
                      <div v-for="tool in item.meta.toolCalls" :key="tool.id" class="hall-tool-viz__item" :class="`hall-tool-viz__item--${tool.status}`">
                        <div class="hall-tool-viz__header">
                          <div class="hall-tool-viz__icon">
                            <!-- 运行中 -->
                            <svg v-if="tool.status === 'start'" class="hall-tool-viz__spinner" viewBox="0 0 24 24" fill="none">
                              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-dasharray="31.4" stroke-dashoffset="10" />
                            </svg>
                            <!-- 完成 -->
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                          </div>
                          <div class="hall-tool-viz__name">{{ getToolDisplayName(tool.name) }}</div>
                          <div v-if="tool.status === 'start'" class="hall-tool-viz__status">执行中...</div>
                          <div v-else class="hall-tool-viz__status hall-tool-viz__status--done">已完成</div>
                        </div>
                      </div>
                    </div>

                    <!-- 思考阶段提示 -->
                    <div v-if="item.author_role === 'bot' && item.meta?.thinkingMessage" class="hall-thinking">
                      <div class="hall-thinking__dot"></div>
                      <div class="hall-thinking__dot"></div>
                      <div class="hall-thinking__dot"></div>
                      <span class="hall-thinking__text">{{ item.meta.thinkingMessage }}</span>
                    </div>

                    <div v-if="isAdmin && item.author_role === 'bot' && botToolReceipts(item).length" class="hall-bubble__tool-receipts">
                      <div class="hall-bubble__tool-receipts-head">执行回执</div>
                      <template v-for="(receipt, receiptIdx) in botToolReceipts(item)" :key="`${item.id}-tool-${receiptIdx}`">
                        <!-- Special rendering for switch_model tool -->
                        <div v-if="receipt.specialType === 'switch_model'" class="hall-model-switch" :class="{ 'is-error': !receipt.ok }">
                          <div class="hall-model-switch__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="16 3 21 3 21 8"></polyline>
                              <line x1="4" y1="20" x2="21" y2="3"></line>
                              <polyline points="21 16 21 21 16 21"></polyline>
                              <line x1="15" y1="15" x2="21" y2="21"></line>
                              <line x1="4" y1="4" x2="9" y2="9"></line>
                            </svg>
                          </div>
                          <div class="hall-model-switch__content">
                            <div class="hall-model-switch__title">
                              <strong>{{ receipt.name }}</strong>
                              <span :class="receipt.ok ? 'hall-model-switch__status--success' : 'hall-model-switch__status--error'">
                                {{ receipt.ok ? (receipt.result?.verified ? '已验证' : '已切换') : '失败' }}
                              </span>
                            </div>
                            <div v-if="receipt.result?.new_config" class="hall-model-switch__config">
                              <div v-if="receipt.result?.new_config?.model" class="hall-model-switch__model">
                                <span class="hall-model-switch__label">对话模型</span>
                                <span class="hall-model-switch__value">{{ receipt.result.new_config.model }}</span>
                              </div>
                              <div v-if="receipt.result?.new_config?.vision_model" class="hall-model-switch__model">
                                <span class="hall-model-switch__label">视觉模型</span>
                                <span class="hall-model-switch__value">{{ receipt.result.new_config.vision_model }}</span>
                              </div>
                            </div>
                            <div v-if="receipt.summary" class="hall-model-switch__summary">{{ receipt.summary }}</div>
                          </div>
                        </div>
                        <!-- Default rendering for other tools -->
                        <article v-else class="hall-tool-receipt" :class="{ 'is-error': !receipt.ok }">
                          <div class="hall-tool-receipt__top">
                            <strong>{{ receipt.name }}</strong>
                            <span>{{ receipt.ok ? '成功' : '失败' }}</span>
                          </div>
                          <div v-if="receipt.summary" class="hall-tool-receipt__summary">{{ receipt.summary }}</div>
                        </article>
                      </template>
                    </div>

                    <div v-if="item.attachments?.length" class="hall-bubble__attachments">
                      <div
                        v-for="attachment in item.attachments"
                        :key="`${String(item.id)}-${attachment.path || attachment.name}`"
                        class="hall-attachment"
                      >
                        <div class="hall-attachment__head">
                          <div class="min-w-0">
                            <p class="truncate font-medium">{{ attachment.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ attachment.mime_type || '文件' }}{{ attachment.size ? ` · ${formatFileSize(attachment.size)}` : '' }}</p>
                          </div>
                          <a
                            v-if="attachmentUrl(attachment)"
                            :href="attachmentUrl(attachment)"
                            target="_blank"
                            rel="noreferrer noopener"
                            class="hall-attachment__open"
                          >打开</a>
                        </div>
                        <img
                          v-if="(attachment.kind === 'image' || attachment.mime_type?.startsWith('image/')) && attachmentUrl(attachment)"
                          :src="attachmentUrl(attachment)"
                          alt="attachment preview"
                          class="hall-attachment__image"
                        />
                        <p v-else-if="attachment.text_content" class="hall-attachment__text">{{ attachment.text_content }}</p>
                      </div>
                    </div>

                    <div class="hall-bubble__status-wrap" v-if="item.isFailed">
                      <div class="hall-bubble__status hall-bubble__status--error">发送失败</div>
                    </div>
                  </div>
                </div>

                <div v-if="isOwnMessage(item)" class="hall-message__self-avatar-wrap">
                  <img v-if="myAvatarUrl" :src="myAvatarUrl" alt="my avatar" class="hall-message__avatar" />
                  <div v-else class="hall-message__avatar hall-message__avatar--fallback hall-message__avatar--self">{{ myInitial }}</div>
                </div>
              </article>
              </template>
            </div>
          </div>

          <div class="hall-composer-wrap">
            <div v-if="sendError" class="hall-state hall-state--error hall-state--inline">{{ sendError }}</div>

            <div v-if="selectedFiles.length" class="hall-composer__files">
              <div v-for="file in selectedFiles" :key="`${file.name}-${file.size}-${file.lastModified}`" class="hall-file-chip">
                <span class="truncate">{{ file.name }}</span>
                <button type="button" class="hall-file-chip__remove" @click="removeFile(file)">&times;</button>
              </div>
            </div>

            <div class="hall-composer-shell">
              <label class="hall-composer__attach">
                <input class="hidden" type="file" multiple @change="handleFilesSelected" />
                <span>+</span>
              </label>

              <div class="hall-composer-card">
                <div class="hall-composer__editor">
                  <div class="hall-composer__mirror" aria-hidden="true">
                    <template v-if="draft">
                      <template v-for="(segment, segmentIndex) in parseMentionSegments(draft)" :key="`draft-${segmentIndex}`">
                        <span :class="mentionSegmentClass(segment)">{{ segment.text }}</span>
                      </template>
                    </template>
                    <span v-else class="hall-composer__placeholder">输入消息，Shift + Enter 换行，@ 可提及在线成员或 Alma</span>
                    <span class="hall-composer__mirror-tail">&#8203;</span>
                  </div>

                  <textarea
                    ref="composerInputRef"
                    v-model="draft"
                    rows="1"
                    class="hall-composer__input"
                    @input="handleDraftInput"
                    @blur="handleComposerBlur"
                    @click="syncMentionState"
                    @keyup="syncMentionState"
                    @keydown="handleComposerKeydown"
                  ></textarea>

                  <div v-if="mentionPanelVisible" class="hall-mention-panel" role="listbox" aria-label="提及成员">
                    <div class="hall-mention-panel__header">
                      <span class="hall-mention-panel__title">{{ mentionState?.query ? '匹配成员' : '快速提及' }}</span>
                      <span class="hall-mention-panel__hint">{{ mentionState?.query ? `@${mentionState.query}` : '回车快速选中' }}</span>
                    </div>
                    <button
                      v-for="(member, memberIndex) in mentionSuggestions"
                      :key="`${member.id}-${member.username}`"
                      type="button"
                      class="hall-mention-panel__item"
                      :class="{ 'hall-mention-panel__item--active': memberIndex === mentionSelectionIndex }"
                      @mousedown.prevent="selectMention(member)"
                    >
                      <img
                        v-if="memberAvatarUrl(member)"
                        :src="memberAvatarUrl(member)"
                        :alt="member.username"
                        class="hall-mention-panel__avatar hall-mention-panel__avatar--image"
                      />
                      <span v-else class="hall-mention-panel__avatar" :class="member.role === 'bot' ? 'hall-mention-panel__avatar--bot' : member.role === 'admin' ? 'hall-mention-panel__avatar--admin' : ''">{{ memberInitial(member) }}</span>
                      <span class="hall-mention-panel__meta">
                        <span class="hall-mention-panel__name-row">
                          <span class="hall-mention-panel__name">@{{ member.username }}</span>
                          <span class="hall-mention-panel__tag">{{ member.role === 'bot' ? 'Bot' : member.role === 'admin' ? 'ADMIN' : '在线' }}</span>
                        </span>
                        <span class="hall-mention-panel__status">{{ member.role === 'bot' ? '活泼助理 Alma' : member.is_typing ? '正在输入' : '在线成员' }}</span>
                      </span>
                    </button>
                  </div>
                </div>

                <button type="button" class="hall-composer__send" :disabled="sending" @click="submitMessage">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 3L10 14"></path>
                    <path d="M21 3L14 21L10 14L3 10L21 3Z"></path>
                  </svg>
                  <span class="sr-only">{{ sending ? '发送中' : '发送消息' }}</span>
                </button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- Bot 头像右键菜单 (仅管理员可见) -->
  <Teleport to="body">
    <div
      v-if="botAvatarMenuVisible"
      class="hall-ctx-menu"
      :style="{ top: botAvatarMenuY + 'px', left: botAvatarMenuX + 'px' }"
      @click.stop
    >
      <button type="button" class="hall-ctx-menu__item" @click="triggerBotAvatarUpload">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        修改 Alma 头像
      </button>
    </div>
    <!-- 隐藏的文件上传 input -->
    <input
      ref="botAvatarFileInput"
      type="file"
      accept="image/*"
      class="sr-only"
      @change="handleBotAvatarFileChange"
    />
  </Teleport>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

definePageMeta({
  layout: 'default',
  ssr: false, // 禁用 SSR，因为页面依赖 localStorage 认证
})

const auth = useAuth()
const route = useRoute()
const { apiBase, apiFetch, resolveMediaUrl, getAuthHeaders } = useApi()

// Text-to-Speech for Alma's messages
const { isSpeaking, initVoices, speak, stop, toggle: toggleTTS } = useTTS()
const ttsInitialized = ref(false)
const currentSpeakingMessageId = ref(null)

// Initialize TTS on mount
onMounted(() => {
  if (!ttsInitialized.value) {
    initVoices()
    ttsInitialized.value = true
  }
})

// Stop TTS when component unmounts
onBeforeUnmount(() => {
  stop()
})

// Toggle TTS for a specific message
const toggleMessageTTS = async (messageId, content) => {
  if (currentSpeakingMessageId.value === messageId) {
    stop()
    currentSpeakingMessageId.value = null
  } else {
    // Stop any current speech first
    stop()
    currentSpeakingMessageId.value = messageId
    await speak(content)
    currentSpeakingMessageId.value = null
  }
}

const botProfile = reactive({
  username: 'Alma',
  avatar: '',
  aliases: ['alma', 'siliconbot', 'hostbot'],
})

const channelTabs = [
  { slug: 'public-lobby', shortTitle: '大厅', title: '社区大厅', description: '开放聊天、机器人协作与实时交流。' },
  { slug: 'concept', shortTitle: '概念', title: '概念聊天室', description: '概念验证、灵感草图与原型讨论。' },
]

const buildBotMember = (overrides = {}) => ({
  id: 'bot',
  username: botProfile.username,
  avatar: botProfile.avatar || null,
  role: 'bot',
  is_typing: false,
  ...overrides,
})

const loading = ref(false)
const loadError = ref('')
const sending = ref(false)
const sendError = ref('')
const draft = ref('')
const messages = ref([])
const settingsOpen = ref(false)

// Bot 头像右键菜单状态 (管理员专用)
const isAdmin = computed(() => auth.user.value?.role === 'admin')
const botAvatarMenuVisible = ref(false)
const botAvatarMenuX = ref(0)
const botAvatarMenuY = ref(0)
const botAvatarFileInput = ref(null)
const botAvatarUploading = ref(false)

function openBotAvatarMenu(event) {
  botAvatarMenuX.value = event.clientX
  botAvatarMenuY.value = event.clientY
  botAvatarMenuVisible.value = true
}

function closeBotAvatarMenu() {
  botAvatarMenuVisible.value = false
}

function triggerBotAvatarUpload() {
  closeBotAvatarMenu()
  botAvatarFileInput.value?.click()
}

async function handleBotAvatarFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  botAvatarUploading.value = true
  try {
    const form = new FormData()
    form.append('avatar', file)
    const res = await $fetch(`${apiBase}/admin/bot/avatar`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: form,
    })
    const newAvatar = res?.data?.avatar
    if (newAvatar) {
      botProfile.avatar = newAvatar
    }
  } catch (err) {
    console.error('Bot avatar upload failed:', err)
  } finally {
    botAvatarUploading.value = false
    // 清空 input 以便下次再次触发 change 事件
    if (botAvatarFileInput.value) botAvatarFileInput.value.value = ''
  }
}
const settingsSaving = ref(false)
const settingsError = ref('')
const customBackgroundFile = ref(null)
const activeMutes = ref([])
const muteDurationMinutes = ref(30)
const muteSubmittingUserId = ref(null)
const settingsPreviewMode = ref('admin-commands')
const copiedAdminCommand = ref('')
const recalls = ref([])
const hoveredMessageId = ref(null)
const recallingId = ref(null)
const botReplyPending = ref(false)
const settingsForm = reactive({
  themeVariant: 'classic',
  hideBot: false,
  customBackgroundPath: '',
  removeCustomBackground: false,
})
const members = ref([buildBotMember()])
const selectedFiles = ref([])
const onlineCount = ref(0)
const typingUsers = ref([])
const activeTypingUsers = computed(() => {
  const users = [...typingUsers.value]

  if (botReplyPending.value) {
    users.unshift(buildBotMember({ is_typing: true }))
  }

  if (typingActive.value && auth.user.value) {
    if (!users.find(u => Number(u.id) === Number(auth.user.value.id))) {
      users.unshift(auth.user.value)
    }
  }

  const uniqueUsers = []
  const seen = new Set()

  for (const user of users) {
    const key = String(user?.id ?? user?.username ?? '')
    if (!key || seen.has(key)) continue
    seen.add(key)
    uniqueUsers.push(user)
  }

  return uniqueUsers
})
const messageViewportRef = ref(null)
const composerInputRef = ref(null)
const mentionState = ref(null)
const mentionSelectionIndex = ref(0)

let pollTimer = null
let presenceTimer = null
let typingStopTimer = null
let lastTypingSentAt = 0
const typingActive = ref(false)
const pollingMessages = ref(false)
const pollingRecalls = ref(false)
const heartbeating = ref(false)

const currentChannel = computed(() => {
  const slug = String(route.params.channel || 'public-lobby')
  return channelTabs.find((item) => item.slug === slug)?.slug || 'public-lobby'
})

const currentChannelMeta = computed(() => {
  return channelTabs.find((item) => item.slug === currentChannel.value) || channelTabs[0]
})

const recalledMessageIds = computed(() => new Set(
  recalls.value
    .map((item) => Number(item?.message_id))
    .filter((id) => Number.isFinite(id) && id > 0),
))

const visibleMessages = computed(() => {
  const msgs = messages.value
  const recalled = recalledMessageIds.value
  const hideBot = settingsForm.hideBot
  const result = []

  for (const msg of msgs) {
    const msgId = Number(msg.id)
    if (recalled.has(msgId)) {
      continue
    }
    if (hideBot && msg.author_role === 'bot') {
      continue
    }
    result.push(msg)
  }

  return result
})

const mergedTimeline = computed(() => {
  const items = []

  for (const msg of visibleMessages.value) {
    if (msg.message_type === 'system_ban' || msg.message_type === 'system_notice') {
      items.push({ ...msg, type: 'system', sortKey: `msg-${msg.id}` })
    } else {
      items.push({ ...msg, type: 'message', sortKey: `msg-${msg.id}` })
    }
  }

  for (const r of recalls.value) {
    items.push({ ...r, type: 'recall', sortKey: `recall-${r.id}` })
  }

  items.sort((a, b) => {
    const ta = new Date(a.created_at || 0).getTime()
    const tb = new Date(b.created_at || 0).getTime()
    return ta - tb
  })

  return items
})

const canRecall = (message) => {
  if (!message || !message.id || message.isPending) return false
  const isAdmin = auth.user.value?.role === 'admin'
  const isOwner = Number(message.user_id) === Number(auth.user.value?.id)
  const targetRole = String(message.author_role || message.user?.role || '')

  if (isAdmin) {
    return isOwner || targetRole === 'user'
  }

  if (!isOwner) return false
  const created = new Date(message.created_at)
  return (Date.now() - created.getTime()) < 120000
}

const recallMessage = async (message) => {
  if (recallingId.value) return
  recallingId.value = message.id
  try {
    const response = await apiFetch(channelApiPath(`messages/${message.id}`), { method: 'DELETE' })

    // Remove from local messages
    messages.value = messages.value.filter(m => m.id !== message.id)

    // Add recall record
    if (response?.data?.recall) {
      recalls.value.push(response.data.recall)
    }

    // 撤回成功 - 将消息内容回显到输入框（带动画效果）
    if (message.content && message.content.trim()) {
      draft.value = message.content
      // 聚焦到输入框
      nextTick(() => {
        const composer = document.querySelector('.hall-composer__input')
        if (composer) {
          composer.focus()
          // 将光标移到末尾
          const len = composer.value?.length || 0
          composer.setSelectionRange(len, len)
        }
      })
    }
  } catch (e) {
    sendError.value = extractApiError(e, '撤回失败')
  } finally {
    recallingId.value = null
  }
}

const muteCandidates = computed(() => {
  return members.value.filter((member) => {
    if (!member || member.id === 'bot') return false
    if (Number(member.id) === Number(auth.user.value?.id)) return false
    if (member.role === 'admin') return false
    return true
  })
})

const adminCommandPreviews = computed(() => {
  const channelTitle = currentChannelMeta.value?.title || '当前频道'

  return [
    {
      title: '禁言成员',
      description: `让 Alma 在 ${channelTitle} 内对指定成员限时禁言。`,
      command: '@Alma 把 @用户名 禁言 30 分钟，理由：刷屏',
    },
    {
      title: '解除禁言',
      description: '解除某位成员在当前频道的禁言状态。',
      command: '@Alma 解除 @用户名 的禁言',
    },
    {
      title: '封禁用户',
      description: '对严重违规用户执行全站封禁，并附带原因。',
      command: '@Alma 封禁 @用户名 7 天，理由：恶意辱骂他人',
    },
    {
      title: '撤回消息',
      description: '当你明确知道消息 ID 时，让 Alma 撤回该消息。',
      command: '@Alma 撤回消息 12345',
    },
    {
      title: '发布系统通知',
      description: '以系统身份向当前频道发布一条管理公告。',
      command: '@Alma 发送系统通知：今晚 22:00 开始维护，请提前保存内容',
    },
    {
      title: '查询频道状态',
      description: '让 Alma 主动读取在线人数、消息量、禁言情况。',
      command: '@Alma 帮我看下当前频道在线情况和今天的消息统计',
    },
  ]
})

const hallPageClass = computed(() => ({
  'hall-page--claude': settingsForm.themeVariant === 'claude',
  'hall-page--custom': settingsForm.themeVariant === 'custom',
}))

const hallPageStyle = computed(() => {
  if (settingsForm.themeVariant !== 'custom') return {}

  const backgroundPath = customBackgroundFile.value
    ? URL.createObjectURL(customBackgroundFile.value)
    : resolveMediaUrl(settingsForm.customBackgroundPath)

  return backgroundPath ? { '--hall-custom-bg': `url(${backgroundPath})` } : {}
})

const myAvatarUrl = computed(() => resolveMediaUrl(auth.user.value?.avatar))
const myInitial = computed(() => auth.user.value?.username?.[0]?.toUpperCase() || '我')
const onlineMembers = computed(() => members.value.filter((member) => member.id !== 'bot'))
const visibleMembers = computed(() => onlineMembers.value.slice(0, 3))
const crowdOverflowCount = computed(() => Math.max(onlineMembers.value.length - visibleMembers.value.length, 0))
const hasOverflowMembers = computed(() => crowdOverflowCount.value > 0)

const mentionSortValue = (member) => String(member?.username || '').trim().toLocaleLowerCase('zh-CN')

const mentionBucketKey = (member) => {
  const username = String(member?.username || '').trim()
  if (!username) return ''

  const normalized = username.normalize('NFKD')
  const ascii = normalized.match(/[a-z0-9]/i)
  if (ascii?.[0]) return ascii[0].toLowerCase()

  return Array.from(username)[0]?.toLowerCase() || ''
}

const mentionableMembers = computed(() => {
  const pool = []
  const seen = new Set()

  for (const member of members.value) {
    if (!member?.username) continue
    if (member.id !== 'bot' && Number(member.id) === Number(auth.user.value?.id)) continue

     if (member.role === 'bot' || member.id === 'bot') {
       continue
     }

    const key = String(member.username).toLowerCase()
    if (seen.has(key)) continue
    seen.add(key)
    pool.push(member)
  }

  pool.sort((left, right) => mentionSortValue(left).localeCompare(mentionSortValue(right), 'zh-CN', { sensitivity: 'base' }))

  return [buildBotMember(), ...pool]
})

const validMentionNames = computed(() => new Set(mentionableMembers.value.map((member) => String(member.username).toLowerCase())))

const mentionSuggestions = computed(() => {
  if (!mentionState.value) return []

  const query = mentionState.value.query.trim().toLowerCase()
  const [botMember, ...regularMembers] = mentionableMembers.value

  if (!query) {
    const shortlisted = []
    const seenBuckets = new Set()

    for (const member of regularMembers) {
      const bucket = mentionBucketKey(member)
      if (!bucket || seenBuckets.has(bucket)) continue

      seenBuckets.add(bucket)
      shortlisted.push(member)

      if (shortlisted.length >= 5) break
    }

    return botMember ? [botMember, ...shortlisted] : shortlisted
  }

  return mentionableMembers.value.filter((member) => {
    if (member.role === 'bot') return true
    return String(member.username).toLowerCase().includes(query)
  })
})

const mentionPanelVisible = computed(() => Boolean(mentionState.value) && mentionSuggestions.value.length > 0)

const lastMessageId = computed(() => {
  return messages.value.reduce((max, message) => {
    const numericId = Number(message.id)
    return Number.isFinite(numericId) ? Math.max(max, numericId) : max
  }, 0)
})

const typingSummary = computed(() => {
  if (!activeTypingUsers.value.length) return ''

  const names = activeTypingUsers.value.map((item) => item.username || '成员')
  if (names.length === 1) return `${names[0]} 输入中`
  if (names.length === 2) return `${names[0]}、${names[1]} 输入中`
  return `${names[0]}、${names[1]} 等 ${names.length} 人输入中`
})

const normalizeMessage = (message) => ({
  ...message,
  isPending: Boolean(message?.isPending),
  isFailed: Boolean(message?.isFailed),
})

const mergeMessages = (incoming) => {
  const messageMap = new Map(messages.value.map((message) => [String(message.id), message]))

  for (const message of incoming.filter(Boolean).map(normalizeMessage)) {
    messageMap.set(String(message.id), {
      ...messageMap.get(String(message.id)),
      ...message,
    })
  }

  messages.value = [...messageMap.values()].sort((left, right) => {
    const leftId = Number(left.id)
    const rightId = Number(right.id)

    if (Number.isFinite(leftId) && Number.isFinite(rightId)) {
      return leftId - rightId
    }

    return String(left.id).localeCompare(String(right.id))
  })
}

const applyPresence = (presence) => {
  onlineCount.value = presence?.online_count ?? 0
  typingUsers.value = presence?.typing_users ?? []
  const dynamicMembers = presence?.members ?? []
  members.value = [
    ...dynamicMembers,
    buildBotMember(),
  ]
}

const isOwnMessage = (message) => Number(message.user_id) === Number(auth.user.value?.id)

const messageInitial = (message) => {
  if (message.author_role === 'bot') return botProfile.username.slice(0, 1).toUpperCase() || 'A'
  return message.author_name?.[0]?.toUpperCase() || '?'
}

const memberInitial = (member) => {
  if (member.role === 'bot') return botProfile.username.slice(0, 1).toUpperCase() || 'A'
  return member.username?.[0]?.toUpperCase() || '?'
}

const messageAvatarUrl = (message) => {
  if (message.author_role === 'bot') return resolveMediaUrl(botProfile.avatar)
  return resolveMediaUrl(message.user?.avatar)
}

const memberAvatarUrl = (member) => {
  if (member.role === 'bot') return resolveMediaUrl(botProfile.avatar)
  return resolveMediaUrl(member.avatar)
}

const attachmentUrl = (attachment) => {
  if (attachment.local_url) return attachment.local_url
  return resolveMediaUrl(attachment.path)
}

const avatarToneClass = (message) => {
  if (message.author_role === 'bot') return 'hall-message__avatar--bot'
  if (message.author_role === 'admin') return 'hall-message__avatar--admin'
  if (isOwnMessage(message)) return 'hall-message__avatar--self'
  return 'hall-message__avatar--user'
}

const formatMessageTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
}

const formatFileSize = (size) => {
  const value = Number(size || 0)
  if (value >= 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB`
  if (value >= 1024) return `${Math.round(value / 1024)} KB`
  return `${value} B`
}

const formatMuteTime = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const channelApiPath = (segment) => `/chat/channels/${currentChannel.value}/${segment}`

const botAliasPattern = computed(() => new RegExp(`@(?:${botProfile.aliases.join('|')})`, 'iu'))

const shouldExpectBotReply = (content) => botAliasPattern.value.test(String(content || ''))

const applyBotMeta = (payload) => {
  botProfile.username = String(payload?.bot_name || botProfile.username || 'Alma')
  botProfile.avatar = String(payload?.bot_avatar || botProfile.avatar || '')
}

const applySettingsPayload = (payload) => {
  const settings = payload?.settings || {}
  settingsForm.themeVariant = settings.theme_variant || 'classic'
  settingsForm.hideBot = Boolean(settings.hide_bot)
  settingsForm.customBackgroundPath = settings.custom_background_path || ''
  settingsForm.removeCustomBackground = false
  activeMutes.value = payload?.mute_list || []
}

const openSettingsModal = () => {
  settingsError.value = ''
  copiedAdminCommand.value = ''
  settingsPreviewMode.value = 'admin-commands'
  settingsOpen.value = true
}

const closeSettingsModal = () => {
  settingsOpen.value = false
  settingsError.value = ''
}

const handleCustomBackgroundSelected = (event) => {
  const file = event.target.files?.[0] || null
  customBackgroundFile.value = file
  if (file) {
    settingsForm.themeVariant = 'custom'
    settingsForm.removeCustomBackground = false
  }
  event.target.value = ''
}

const clearCustomBackground = () => {
  customBackgroundFile.value = null
  settingsForm.customBackgroundPath = ''
  settingsForm.removeCustomBackground = true
}

const saveSettings = async () => {
  settingsSaving.value = true
  settingsError.value = ''

  try {
    const body = new FormData()
    body.append('theme_variant', settingsForm.themeVariant)
    body.append('hide_bot', settingsForm.hideBot ? '1' : '0')
    body.append('remove_custom_background', settingsForm.removeCustomBackground ? '1' : '0')

    if (customBackgroundFile.value) {
      body.append('custom_background', customBackgroundFile.value)
    }

    const response = await $fetch(`${apiBase}${channelApiPath('settings')}`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body,
    })

    applySettingsPayload(response.data)
    customBackgroundFile.value = null
    closeSettingsModal()
  } catch (error) {
    settingsError.value = extractApiError(error, '设置保存失败，请稍后重试')
  } finally {
    settingsSaving.value = false
  }
}

const muteMember = async (userId) => {
  muteSubmittingUserId.value = userId
  settingsError.value = ''

  try {
    const response = await apiFetch(channelApiPath('mute'), {
      method: 'POST',
      body: {
        user_id: userId,
        minutes: muteDurationMinutes.value,
      },
    })
    activeMutes.value = response.data?.mute_list || []
  } catch (error) {
    settingsError.value = extractApiError(error, '禁言设置失败，请稍后重试')
  } finally {
    muteSubmittingUserId.value = null
  }
}

const unmuteMember = async (userId) => {
  settingsError.value = ''

  try {
    const response = await apiFetch(channelApiPath(`mute/${userId}`), {
      method: 'DELETE',
    })
    activeMutes.value = response.data?.mute_list || []
  } catch (error) {
    settingsError.value = extractApiError(error, '解除禁言失败，请稍后重试')
  }
}

const copyAdminCommand = async (command) => {
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(command)
    } else {
      const textarea = document.createElement('textarea')
      textarea.value = command
      textarea.setAttribute('readonly', 'true')
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      textarea.style.pointerEvents = 'none'
      document.body.appendChild(textarea)
      textarea.focus()
      textarea.select()
      textarea.setSelectionRange(0, textarea.value.length)

      const copied = document.execCommand('copy')
      document.body.removeChild(textarea)

      if (!copied) {
        throw new Error('copy command failed')
      }
    }

    copiedAdminCommand.value = command
    setTimeout(() => {
      if (copiedAdminCommand.value === command) {
        copiedAdminCommand.value = ''
      }
    }, 1600)
  } catch (error) {
    settingsError.value = '复制失败，请手动选择文本复制'
  }
}

const botToolReceipts = (message) => {
  const receipts = Array.isArray(message?.meta?.tool_results) ? message.meta.tool_results : []

  return receipts.map((item) => {
    const name = String(item?.name || 'unknown_tool')
    const result = item?.result || {}
    const ok = Boolean(item?.ok)

    let summary = ''
    let specialType = null // For special rendering

    if (typeof result?.error === 'string' && result.error) {
      summary = result.error
    } else if (name === 'mute_user') {
      summary = `目标 ${result?.target || '未知用户'}，${result?.minutes || 0} 分钟`
    } else if (name === 'unmute_user') {
      summary = `目标 ${result?.target || '未知用户'}`
    } else if (name === 'ban_user') {
      summary = `目标 ${result?.target || '未知用户'}，截止 ${formatMuteTime(result?.banned_until) || '未知时间'}`
    } else if (name === 'recall_message') {
      summary = `消息 #${result?.message_id || 0}`
    } else if (name === 'send_system_notice') {
      summary = String(result?.content || '系统通知已发布')
    } else if (name === 'get_online_members') {
      summary = `${result?.online_count || 0} 人在线，${result?.typing_count || 0} 人输入中`
    } else if (name === 'get_channel_stats') {
      summary = `今日 ${result?.messages_today || 0} 条消息，总计 ${result?.total_messages || 0} 条`
    } else if (name === 'lookup_user') {
      summary = result?.found ? `用户 ${result?.user?.username || '未知'}，角色 ${result?.user?.role || '未知'}` : '未找到用户'
    } else if (name === 'search_web') {
      summary = '已执行联网检索'
    } else if (name === 'recall_memory') {
      summary = `命中 ${Array.isArray(result?.items) ? result.items.length : 0} 条记忆`
    } else if (name === 'switch_model') {
      // Special rendering for model switching
      specialType = 'switch_model'
      summary = result?.success ? `切换成功${result?.verified ? ' ✓' : ''}` : '切换失败'
    } else if (name === 'convert_units') {
      const converted = result?.converted
      summary = converted ? `${result.original.value}${result.original.unit} = ${converted.value}${converted.unit}` : '单位转换'
    } else if (name === 'calculate') {
      summary = result?.result !== undefined ? `= ${result.result}` : '计算'
    }

    return { name, ok, summary, specialType, result }
  })
}

// 工具名称中文映射
const toolNameMap = {
  get_current_time: '获取当前时间',
  search_web: '网络搜索',
  recall_memory: '记忆检索',
  get_online_members: '在线成员',
  get_channel_stats: '频道统计',
  lookup_user: '用户查询',
  mute_user: '禁言用户',
  unmute_user: '解除禁言',
  ban_user: '封禁用户',
  recall_message: '撤回消息',
  send_system_notice: '系统通知',
  notify_user: '用户通知',
  convert_units: '单位转换',
  calculate: '数学计算',
  get_weather: '天气查询',
  switch_model: '模型切换',
}

const getToolDisplayName = (name) => {
  return toolNameMap[name] || name
}

// 获取工具结果预览文本
const getToolResultPreview = (tool) => {
  if (!tool.result) return ''
  const r = tool.result

  if (r.error) return `错误: ${r.error}`

  if (tool.name === 'get_current_time') {
    return `${r.date || ''} ${r.time || ''} ${r.weekday || ''}`
  }
  if (tool.name === 'get_online_members') {
    return `${r.online_count || 0} 人在线`
  }
  if (tool.name === 'get_channel_stats') {
    return `今日 ${r.messages_today || 0} 条`
  }
  if (tool.name === 'lookup_user') {
    return r.found ? `找到: ${r.user?.username || ''}` : '未找到用户'
  }
  if (tool.name === 'search_web') {
    return r.results ? `找到 ${Array.isArray(r.results) ? r.results.length : 0} 条结果` : '搜索完成'
  }
  if (tool.name === 'recall_memory') {
    return `命中 ${Array.isArray(r.items) ? r.items.length : 0} 条记忆`
  }
  if (tool.name === 'get_weather') {
    return r.temperature ? `${r.city || ''} ${r.temperature}${r.unit || '°C'}` : '天气查询完成'
  }
  if (tool.name === 'convert_units') {
    return r.converted ? `${r.original.value}${r.original.unit} = ${r.converted.value}${r.converted.unit}` : ''
  }
  if (tool.name === 'calculate') {
    return r.result !== undefined ? `= ${r.result}` : ''
  }

  // 通用预览
  const keys = Object.keys(r).filter(k => !['error', 'ok'].includes(k))
  if (keys.length === 0) return '执行完成'
  const first = keys[0]
  const val = r[first]
  if (typeof val === 'string') return String(val).substring(0, 80)
  if (typeof val === 'number') return `${first}: ${val}`
  return `执行完成`
}

const splitMentionToken = (rawValue) => {
  const matched = String(rawValue || '').match(/^([^，。！？；：、,.!?:;)}\]）】》〉]+)([，。！？；：、,.!?:;)}\]）】》〉]*)$/u)

  if (!matched) {
    return {
      mention: String(rawValue || ''),
      suffix: '',
    }
  }

  return {
    mention: matched[1] || '',
    suffix: matched[2] || '',
  }
}

const parseMentionSegments = (value) => {
  const text = String(value || '')
  const regex = /@([^\s@]+)/gu
  const segments = []
  let lastIndex = 0

  for (const match of text.matchAll(regex)) {
    const fullText = match[0] || ''
    const token = match[1] || ''
    const index = match.index ?? 0

    if (index > lastIndex) {
      segments.push({ text: text.slice(lastIndex, index), isMention: false, valid: false })
    }

    const { mention, suffix } = splitMentionToken(token)
    const mentionText = `@${mention}`
    const valid = mention !== '' && validMentionNames.value.has(mention.toLowerCase())
    segments.push({ text: mentionText, isMention: true, valid })

    if (suffix) {
      segments.push({ text: suffix, isMention: false, valid: false })
    }

    lastIndex = index + fullText.length
  }

  if (lastIndex < text.length) {
    segments.push({ text: text.slice(lastIndex), isMention: false, valid: false })
  }

  return segments.length ? segments : [{ text, isMention: false, valid: false }]
}

const mentionSegmentClass = (segment) => {
  if (!segment?.isMention) return ''
  return segment.valid ? 'hall-mention hall-mention--valid' : 'hall-mention hall-mention--invalid'
}

const isViewportNearBottom = (threshold = 96) => {
  const viewport = messageViewportRef.value
  if (!viewport) return true

  const distance = viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight
  return distance <= threshold
}

const setViewportToBottom = (behavior = 'auto') => {
  if (!messageViewportRef.value) return

  messageViewportRef.value.scrollTo({
    top: messageViewportRef.value.scrollHeight,
    behavior,
  })
}

const resizeComposer = () => {
  if (!composerInputRef.value) return
  composerInputRef.value.style.height = '0px'
  composerInputRef.value.style.height = `${Math.min(composerInputRef.value.scrollHeight, 160)}px`
}

const findMentionContext = () => {
  if (!composerInputRef.value) return null

  const caret = composerInputRef.value.selectionStart ?? draft.value.length
  const beforeCaret = draft.value.slice(0, caret)
  const tokenStart = Math.max(
    beforeCaret.lastIndexOf(' '),
    beforeCaret.lastIndexOf('\n'),
    beforeCaret.lastIndexOf('\t'),
    beforeCaret.lastIndexOf('('),
    beforeCaret.lastIndexOf('['),
    beforeCaret.lastIndexOf('{'),
    beforeCaret.lastIndexOf('，'),
    beforeCaret.lastIndexOf('。'),
    beforeCaret.lastIndexOf('！'),
    beforeCaret.lastIndexOf('？'),
    beforeCaret.lastIndexOf('、')
  ) + 1
  const token = beforeCaret.slice(tokenStart)
  const match = token.match(/^@([^\s@]*)$/u)
  if (!match) return null

  return {
    start: tokenStart,
    end: caret,
    query: match[1] || '',
  }
}

const syncMentionState = () => {
  mentionState.value = findMentionContext()

  if (!mentionPanelVisible.value) {
    mentionSelectionIndex.value = 0
    return
  }

  if (mentionSelectionIndex.value >= mentionSuggestions.value.length) {
    mentionSelectionIndex.value = 0
  }
}

const selectMention = async (member) => {
  if (!mentionState.value || !composerInputRef.value) return

  const replacement = `@${member.username} `
  const nextDraft = `${draft.value.slice(0, mentionState.value.start)}${replacement}${draft.value.slice(mentionState.value.end)}`
  const nextCaret = mentionState.value.start + replacement.length

  draft.value = nextDraft
  mentionState.value = null
  mentionSelectionIndex.value = 0

  await nextTick()
  composerInputRef.value.focus()
  composerInputRef.value.setSelectionRange(nextCaret, nextCaret)
  resizeComposer()
  syncMentionState()
  await handleDraftInput()
}

const handleComposerKeydown = async (event) => {
  if (mentionPanelVisible.value) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      mentionSelectionIndex.value = (mentionSelectionIndex.value + 1) % mentionSuggestions.value.length
      return
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault()
      mentionSelectionIndex.value = (mentionSelectionIndex.value - 1 + mentionSuggestions.value.length) % mentionSuggestions.value.length
      return
    }

    if (event.key === 'Tab' || (event.key === 'Enter' && !event.shiftKey)) {
      event.preventDefault()
      await selectMention(mentionSuggestions.value[mentionSelectionIndex.value])
      return
    }

    if (event.key === 'Escape') {
      mentionState.value = null
      mentionSelectionIndex.value = 0
      return
    }
  }

  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    await submitMessage()
  }
}

const scrollToBottom = async (behavior = 'auto') => {
  await nextTick()
  setViewportToBottom(behavior)
}

const snapToLatestMessages = async ({ force = false, smooth = false } = {}) => {
  if (!force && !isViewportNearBottom()) return
  await scrollToBottom(smooth ? 'smooth' : 'auto')
}

const fetchBootstrap = async () => {
  if (!auth.isLoggedIn.value) {
    return
  }

  loading.value = true
  loadError.value = ''
  pollingMessages.value = false
  pollingRecalls.value = false
  heartbeating.value = false

  try {
    const response = await apiFetch(channelApiPath('bootstrap'))

    // Handle response - Laravel returns {data: {messages: [...]}}
    const responseData = response?.data ?? response ?? {}
    const messagesData = Array.isArray(responseData.messages) ? responseData.messages : []

    // Normalize and set messages
    messages.value = messagesData.map(msg => ({
      ...msg,
      isPending: false,
      isFailed: false,
    }))

    const presenceData = responseData.presence ?? {}
    const settingsData = responseData.settings ?? {}
    const muteListData = responseData.mute_list ?? []

    applyBotMeta(responseData)
    applyPresence(presenceData)
    applySettingsPayload({ settings: settingsData, mute_list: muteListData })
    // Load recall records
    try {
      const recallResponse = await apiFetch(channelApiPath('recalls'))
      recalls.value = recallResponse?.data ?? []
    } catch (e) {
      // silently ignore recall errors
    }
    await snapToLatestMessages({ force: true })
  } catch (error) {
    if (error?.response?.status === 403 && error?.response?.data?.force_logout) {
      auth.logout()
      navigateTo('/login')
      return
    }
    loadError.value = extractApiError(error, '大厅初始化失败，请刷新重试')
  } finally {
    loading.value = false
  }
}

const pollRecalls = async () => {
  if (!auth.isLoggedIn.value) return
  if (pollingRecalls.value) return
  if (import.meta.client && document.hidden) return

  pollingRecalls.value = true
  try {
    const response = await apiFetch(channelApiPath('recalls'))
    recalls.value = response.data ?? []
  } catch {}
  finally {
    pollingRecalls.value = false
  }
}

const retryBootstrap = async () => {
  await fetchBootstrap()
  await heartbeat()
}

const pollMessages = async () => {
  if (!auth.isLoggedIn.value) return
  if (pollingMessages.value) return
  if (import.meta.client && document.hidden) return

  pollingMessages.value = true
  try {
    const response = await apiFetch(`${channelApiPath('messages')}?after_id=${lastMessageId.value}`)
    const nextMessages = response.data?.messages ?? []
    applyPresence(response.data?.presence)
    await pollRecalls()

    if (!nextMessages.length) return

    const shouldStickToBottom = isViewportNearBottom()

    mergeMessages(nextMessages)
    await snapToLatestMessages({ force: shouldStickToBottom, smooth: shouldStickToBottom })
  } catch {}
  finally {
    pollingMessages.value = false
  }
}

const heartbeat = async () => {
  if (!auth.isLoggedIn.value) return
  if (heartbeating.value) return
  if (import.meta.client && document.hidden) return

  heartbeating.value = true
  try {
    const response = await apiFetch(channelApiPath('presence'), { method: 'POST' })
    applyPresence(response.data)
  } catch {}
  finally {
    heartbeating.value = false
  }
}

const sendTypingState = async (typing) => {
  if (!auth.isLoggedIn.value) return
  if (typingActive.value === typing && typing && (Date.now() - lastTypingSentAt) < 1800) return

  typingActive.value = typing
  lastTypingSentAt = Date.now()

  try {
    const response = await apiFetch(channelApiPath('typing'), {
      method: 'POST',
      body: { typing },
    })
    applyPresence(response.data)
  } catch {}
}

const handleDraftInput = async () => {
  resizeComposer()
  syncMentionState()

  if (!draft.value.trim()) {
    clearTimeout(typingStopTimer)
    await sendTypingState(false)
    return
  }

  await sendTypingState(true)

  clearTimeout(typingStopTimer)
  typingStopTimer = window.setTimeout(() => {
    sendTypingState(false)
  }, 3200)
}

const handleComposerBlur = () => {
  clearTimeout(typingStopTimer)
  sendTypingState(false)

  window.setTimeout(() => {
    mentionState.value = null
    mentionSelectionIndex.value = 0
  }, 120)
}

const handleFilesSelected = (event) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return
  selectedFiles.value = [...selectedFiles.value, ...files].slice(0, 6)
  event.target.value = ''
}

const removeFile = (file) => {
  selectedFiles.value = selectedFiles.value.filter((item) => item !== file)
}

const buildTempMessage = (tempId, text, files) => ({
  id: tempId,
  channel: currentChannel.value,
  user_id: auth.user.value?.id,
  author_name: auth.user.value?.username,
  author_role: auth.user.value?.role === 'admin' ? 'admin' : 'user',
  message_type: 'message',
  content: text,
  attachments: files.map((file) => ({
    name: file.name,
    size: file.size,
    mime_type: file.type,
    kind: file.type.startsWith('image/') ? 'image' : 'file',
    local_url: file.type.startsWith('image/') ? URL.createObjectURL(file) : '',
  })),
  meta: {},
  reply_to_id: null,
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString(),
  user: auth.user.value ? {
    id: auth.user.value.id,
    username: auth.user.value.username,
    avatar: auth.user.value.avatar,
    role: auth.user.value.role,
  } : null,
  isPending: true,
  isFailed: false,
})

const markTempMessageFailed = (tempId) => {
  messages.value = messages.value.map((message) => {
    if (String(message.id) !== String(tempId)) return message
    return {
      ...message,
      isPending: false,
      isFailed: true,
    }
  })
}

const revokeTempAttachments = (message) => {
  for (const attachment of message.attachments ?? []) {
    if (attachment.local_url) {
      URL.revokeObjectURL(attachment.local_url)
    }
  }
}

const submitMessage = async () => {
  if (sending.value) return

  const text = draft.value
  const trimmed = text.trim()
  if (!trimmed && !selectedFiles.value.length) {
    sendError.value = '消息内容和附件不能同时为空。'
    return
  }

  sending.value = true
  sendError.value = ''

  const tempId = `temp-${Date.now()}`
  const pendingFiles = [...selectedFiles.value]
  const tempMessage = buildTempMessage(tempId, text, pendingFiles)
  const expectingBotReply = shouldExpectBotReply(text)

  mergeMessages([tempMessage])
  await snapToLatestMessages({ force: true, smooth: true })

  draft.value = ''
  selectedFiles.value = []
  mentionState.value = null
  mentionSelectionIndex.value = 0
  clearTimeout(typingStopTimer)
  await sendTypingState(false)
  await nextTick()
  resizeComposer()
  botReplyPending.value = expectingBotReply

  try {
    const body = new FormData()
    if (trimmed) {
      body.append('content', text)
    }

    pendingFiles.forEach((file) => {
      body.append('files[]', file)
    })

    // Use stream=1 when a bot reply is expected so the bot reply
    // comes progressively via SSE instead of synchronously in this response.
    const messagesUrl = `${apiBase}${channelApiPath('messages')}${expectingBotReply ? '?stream=1' : ''}`

    const response = await $fetch(messagesUrl, {
      method: 'POST',
      headers: getAuthHeaders(),
      body,
    })

    messages.value = messages.value.filter((message) => String(message.id) !== String(tempId))
    revokeTempAttachments(tempMessage)
    mergeMessages([response.data?.user_message])
    applyPresence(response.data?.presence)

    if (response.data?.streaming) {
      // Kick off progressive SSE streaming for the bot reply
      streamBotReply(response.data.streaming)
    } else {
      // Non-streaming path (e.g. no bot trigger, or stream flag not set)
      if (response.data?.bot_message) {
        mergeMessages([response.data.bot_message])
      }
      botReplyPending.value = false
    }

    await snapToLatestMessages({ force: true, smooth: true })
  } catch (error) {
    sendError.value = extractApiError(error, '消息发送失败，请稍后重试')
    markTempMessageFailed(tempId)
    botReplyPending.value = false
  } finally {
    sending.value = false
  }
}

/**
 * Consume the SSE stream-reply endpoint and progressively render Alma's response.
 * @param {number} triggerId - The ID of the user message that triggered the bot.
 */
const streamBotReply = async (triggerId) => {
  const streamId = `stream-${Date.now()}`
  let hasReceivedContent = false
  let streamingMsgIndex = -1

  // 工具调用可视化状态
  const toolCalls = [] // { id, name, status: 'start'|'done', ok, result, round }
  let thinkingPhase = '' // '' | 'executing' | 'thinking'
  let thinkingMessage = ''

  const authHeaders = getAuthHeaders()
  const streamUrl = `${apiBase}${channelApiPath('stream-reply')}?trigger_id=${triggerId}`

  let eventBuffer = ''
  let completedMessage = null
  let pendingEventName = ''

  // 创建流式消息气泡（带工具调用可视化）
  const createStreamingMessage = (initialContent = '', extraMeta = {}) => {
    const msg = {
      id: streamId,
      channel: currentChannel.value,
      user_id: null,
      author_name: botProfile.username,
      author_role: 'bot',
      message_type: 'bot',
      content: initialContent,
      attachments: [],
      meta: {
        streamed: true,
        toolCalls: [],
        thinkingPhase: '',
        thinkingMessage: '',
        ...extraMeta,
      },
      reply_to_id: triggerId,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      user: null,
      isStreaming: true,
    }
    return msg
  }

  // 更新工具调用可视化
  const updateToolCalls = () => {
    const idx = messages.value.findIndex((m) => m.id === streamId)
    if (idx !== -1) {
      messages.value[idx] = {
        ...messages.value[idx],
        meta: {
          ...messages.value[idx].meta,
          toolCalls: [...toolCalls],
          thinkingPhase,
          thinkingMessage,
        },
      }
    }
  }

  try {
    const resp = await fetch(streamUrl, { headers: authHeaders })
    if (!resp.ok || !resp.body) throw new Error(`Stream request failed: ${resp.status}`)

    const reader = resp.body.getReader()
    const decoder = new TextDecoder()

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      eventBuffer += decoder.decode(value, { stream: true })
      const lines = eventBuffer.split('\n')
      eventBuffer = lines.pop() ?? ''

      for (const line of lines) {
        const trimmedLine = line.trim()
        if (trimmedLine === '') {
          pendingEventName = ''
          continue
        }
        if (trimmedLine.startsWith('event: ')) {
          pendingEventName = trimmedLine.slice(7).trim()
          continue
        }
        if (trimmedLine.startsWith('data: ')) {
          let parsed
          try { parsed = JSON.parse(trimmedLine.slice(6)) } catch { continue }

          if (pendingEventName === 'done') {
            completedMessage = parsed.message ?? null
          } else if (pendingEventName === 'tool') {
            // 工具执行事件
            const tool = toolCalls.find(t => t.name === parsed.name)
            if (parsed.status === 'start') {
              // 工具开始
              if (!tool) {
                toolCalls.push({
                  id: `tool-${toolCalls.length}-${Date.now()}`,
                  name: parsed.name ?? '',
                  status: 'start',
                  round: parsed.round ?? 1,
                })
              }
              thinkingPhase = 'executing'
              thinkingMessage = `正在执行 ${getToolDisplayName(parsed.name)}...`
            } else if (parsed.status === 'done') {
              // 工具完成
              if (tool) {
                tool.status = 'done'
                tool.ok = parsed.ok !== false
              }
              thinkingPhase = 'thinking'
              thinkingMessage = '正在整理回复...'
            }
            updateToolCalls()
          } else if (parsed.delta) {
            // 流式文本内容 - 这是主要响应
            thinkingPhase = ''
            thinkingMessage = ''

            if (!hasReceivedContent) {
              hasReceivedContent = true
              const streamingMsg = createStreamingMessage(parsed.delta)
              messages.value.push(streamingMsg)
              streamingMsgIndex = messages.value.length - 1
              await snapToLatestMessages({ force: true, smooth: true })
            } else {
              const idx = messages.value.findIndex((m) => m.id === streamId)
              if (idx !== -1) {
                messages.value[idx] = {
                  ...messages.value[idx],
                  content: messages.value[idx].content + parsed.delta,
                }
                await snapToLatestMessages({ smooth: true })
              }
            }
          }
          pendingEventName = ''
        }
      }
    }
  } catch (err) {
    console.warn('[stream-reply] error:', err)
  }

  // Replace streaming bubble with the saved bot message (or remove on failure)
  const idx = messages.value.findIndex((m) => m.id === streamId)
  if (idx !== -1) {
    if (completedMessage) {
      messages.value[idx] = completedMessage
    } else {
      messages.value[idx] = { ...messages.value[idx], isStreaming: false }
    }
  }

  botReplyPending.value = false
  await snapToLatestMessages({ force: true, smooth: true })
}

onMounted(async () => {
  auth.initAuth()
  await auth.refreshMe()
  await fetchBootstrap()
  await heartbeat()
  resizeComposer()
  await snapToLatestMessages({ force: true })

  if (pollTimer) clearInterval(pollTimer)
  if (presenceTimer) clearInterval(presenceTimer)

  pollTimer = window.setInterval(pollMessages, 3000)
  presenceTimer = window.setInterval(heartbeat, 12000)

  document.addEventListener('click', closeBotAvatarMenu)
})

watch(() => currentChannel.value, async () => {
  messages.value = []
  activeMutes.value = []
  recalls.value = []
  pollingMessages.value = false
  pollingRecalls.value = false
  heartbeating.value = false
  await fetchBootstrap()
  await heartbeat()
  await snapToLatestMessages({ force: true })
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (presenceTimer) clearInterval(presenceTimer)
  clearTimeout(typingStopTimer)
  botReplyPending.value = false
  sendTypingState(false)
  document.removeEventListener('click', closeBotAvatarMenu)

  for (const message of messages.value) {
    revokeTempAttachments(message)
  }

  if (hallPageStyle.value['--hall-custom-bg']?.startsWith('url(blob:')) {
    const blobUrl = hallPageStyle.value['--hall-custom-bg'].slice(4, -1)
    URL.revokeObjectURL(blobUrl)
  }
})
</script>

<style scoped>
.hall-page {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background:
    radial-gradient(circle at top, rgba(34, 211, 238, 0.1), transparent 30%),
    linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
}

.hall-page--claude {
  background:
    radial-gradient(circle at top, rgba(217, 119, 6, 0.08), transparent 26%),
    linear-gradient(180deg, #f7f3ea 0%, #efe7d7 100%);
}

.hall-page--custom {
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.18), rgba(15, 23, 42, 0.18)),
    var(--hall-custom-bg, linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%));
  background-size: cover;
  background-position: center;
}

:global(.theme-dark) .hall-page {
  background:
    radial-gradient(circle at top, rgba(34, 211, 238, 0.08), transparent 28%),
    linear-gradient(180deg, #020617 0%, #0f172a 100%);
}

.hall-page__auth-state {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
}

.hall-page__auth-card {
  max-width: 34rem;
  padding: 3rem;
  border-radius: 32px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.88);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
  text-align: center;
}

:global(.theme-dark) .hall-page__auth-card {
  background: rgba(15, 23, 42, 0.88);
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-page__auth-title {
  margin: 0;
  font-size: 2rem;
  color: #0f172a;
}

:global(.theme-dark) .hall-page__auth-title {
  color: #f8fafc;
}

.hall-page__auth-text {
  margin: 1rem 0 0;
  color: #64748b;
  line-height: 1.8;
}

:global(.theme-dark) .hall-page__auth-text {
  color: #94a3b8;
}

.hall-page__auth-actions {
  margin-top: 1.6rem;
  display: flex;
  justify-content: center;
  gap: 0.9rem;
  flex-wrap: wrap;
}

.hall-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 140px;
  padding: 0.95rem 1.5rem;
  border-radius: 999px;
  font-weight: 700;
}

.hall-action-btn--primary {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
  color: #082f49;
}

.hall-action-btn--ghost {
  border: 1px solid rgba(148, 163, 184, 0.2);
  color: #0f172a;
}

:global(.theme-dark) .hall-action-btn--ghost {
  color: #e2e8f0;
  border-color: rgba(56, 189, 248, 0.18);
}

.hall-layout {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr);
  overflow: hidden;
}

.hall-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 1.25rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

:global(.theme-dark) .hall-topbar {
  background: rgba(2, 6, 23, 0.76);
  border-bottom-color: rgba(56, 189, 248, 0.14);
}

.hall-topbar__main {
  display: flex;
  align-items: center;
  gap: 1.3rem;
  min-width: 0;
}

.hall-topbar__side {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.hall-topbar__back {
  color: #64748b;
  font-size: 0.92rem;
  font-weight: 600;
}

:global(.theme-dark) .hall-topbar__back {
  color: #94a3b8;
}

.hall-topbar__channel {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  min-width: 0;
}

.hall-topbar__badge {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, rgba(34, 211, 238, 0.18), rgba(59, 130, 246, 0.16));
  color: #0ea5e9;
  font-size: 1.1rem;
  font-weight: 800;
}

.hall-topbar__title {
  margin: 0;
  font-size: clamp(1.35rem, 1.1rem + 0.5vw, 1.7rem);
  color: #0f172a;
}

:global(.theme-dark) .hall-topbar__title {
  color: #f8fafc;
}

.hall-topbar__info {
  display: flex;
  flex-direction: column;
}

.hall-topbar__presence-wrap {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-top: 0.3rem;
}

.hall-topbar__tabs {
  display: flex;
  gap: 0.45rem;
  margin-top: 0.75rem;
}

.hall-topbar__tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.38rem 0.78rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.55);
  color: #475569;
  font-size: 0.78rem;
  font-weight: 700;
}

.hall-topbar__tab--active {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
  border-color: transparent;
  color: #082f49;
}

.hall-topbar__settings {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.72);
  color: #0f172a;
  font-weight: 700;
}

.hall-topbar__settings-icon {
  display: inline-grid;
  place-items: center;
  width: 1.35rem;
  height: 1.35rem;
  font-size: 0.95rem;
}

.hall-topbar__presence {
  margin: 0;
  color: #64748b;
  font-size: 0.92rem;
  line-height: 1;
}

:global(.theme-dark) .hall-topbar__presence {
  color: #94a3b8;
}

:global(.theme-dark) .hall-topbar__tab,
:global(.theme-dark) .hall-topbar__settings {
  background: rgba(15, 23, 42, 0.7);
  color: #e2e8f0;
  border-color: rgba(56, 189, 248, 0.16);
}

.hall-page--claude .hall-topbar,
.hall-page--claude .hall-composer-wrap {
  background: rgba(250, 245, 235, 0.82);
  border-color: rgba(180, 138, 72, 0.18);
}

.hall-page--claude .hall-bubble,
.hall-page--claude .hall-composer-card,
.hall-page--claude .hall-state,
.hall-page--claude .hall-topbar__crowd,
.hall-page--claude .hall-topbar__settings {
  background: rgba(255, 250, 242, 0.9);
  border-color: rgba(180, 138, 72, 0.18);
}

.hall-page--claude .hall-message--bot .hall-bubble {
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.08), rgba(255, 250, 242, 0.92));
}

.hall-page--claude .hall-topbar__title,
.hall-page--claude .hall-message__author,
.hall-page--claude .hall-topbar__settings {
  color: #5b3416;
}

.hall-page--custom .hall-topbar,
.hall-page--custom .hall-composer-wrap {
  background: rgba(255, 255, 255, 0.22);
  border-color: rgba(255, 255, 255, 0.24);
}

.hall-page--custom .hall-bubble,
.hall-page--custom .hall-attachment,
.hall-page--custom .hall-state,
.hall-page--custom .hall-composer-card,
.hall-page--custom .hall-file-chip,
.hall-page--custom .hall-mention-panel {
  background: rgba(255, 255, 255, 0.92);
  border-color: rgba(148, 163, 184, 0.18);
  color: #334155;
}

.hall-page--custom .hall-composer__attach,
.hall-page--custom .hall-topbar__crowd,
.hall-page--custom .hall-topbar__settings,
.hall-page--custom .hall-topbar__tab {
  background: rgba(255, 255, 255, 0.24);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

.hall-settings-layer {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: grid;
  place-items: center;
  padding: 1.25rem;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.hall-settings-dialog {
  width: min(980px, 100%);
  max-height: min(88vh, 920px);
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  overflow: hidden;
  border-radius: 32px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background:
    radial-gradient(circle at top left, rgba(34, 211, 238, 0.12), transparent 28%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(248, 250, 252, 0.96));
  box-shadow: 0 32px 90px rgba(15, 23, 42, 0.24);
}

.hall-settings__header,
.hall-settings__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.4rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.hall-settings__footer {
  border-bottom: 0;
  border-top: 1px solid rgba(148, 163, 184, 0.16);
}

.hall-settings__eyebrow {
  margin: 0 0 0.3rem;
  color: #0ea5e9;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.hall-settings__title {
  margin: 0;
  font-size: 1.7rem;
  color: #0f172a;
}

.hall-settings__subtitle {
  margin: 0.35rem 0 0;
  color: #64748b;
}

.hall-settings__close,
.hall-settings__ghost,
.hall-settings__save,
.hall-mute-card__remove,
.hall-custom-bg-panel__clear {
  border-radius: 999px;
  padding: 0.72rem 1rem;
  font-weight: 700;
}

.hall-settings__close,
.hall-settings__ghost,
.hall-custom-bg-panel__clear {
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: rgba(255, 255, 255, 0.66);
  color: #334155;
}

.hall-settings__save,
.hall-mute-card__remove {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
  color: #082f49;
}

.hall-settings__body {
  min-height: 0;
  overflow: auto;
  display: grid;
  gap: 1rem;
  padding: 1.2rem 1.4rem;
}

.hall-settings__panel {
  padding: 1.1rem;
  border-radius: 24px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.76);
}

.hall-settings__panel-head h3 {
  margin: 0;
  color: #0f172a;
}

.hall-settings__panel-head p {
  margin: 0.35rem 0 0.9rem;
  color: #64748b;
}

.hall-theme-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.85rem;
}

.hall-theme-card {
  display: grid;
  gap: 0.45rem;
  padding: 1rem;
  border-radius: 22px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(239, 246, 255, 0.88));
  text-align: left;
}

.hall-theme-card--claude {
  background: linear-gradient(180deg, rgba(255, 248, 235, 0.96), rgba(245, 230, 201, 0.86));
}

.hall-theme-card--custom {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.9), rgba(14, 116, 144, 0.64));
  color: #f8fafc;
}

.hall-theme-card.is-active {
  border-color: rgba(14, 165, 233, 0.48);
  box-shadow: 0 16px 36px rgba(14, 165, 233, 0.16);
  transform: translateY(-1px);
}

.hall-settings__switches {
  margin-top: 1rem;
}

.hall-switch-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.95rem 1rem;
  border-radius: 18px;
  background: rgba(248, 250, 252, 0.92);
}

.hall-switch-row strong,
.hall-custom-bg-panel__info strong {
  display: block;
  color: #0f172a;
}

.hall-switch-row small,
.hall-custom-bg-panel__info span {
  color: #64748b;
}

.hall-switch-row__control {
  width: 1.2rem;
  height: 1.2rem;
}

.hall-custom-bg-panel {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  gap: 0.8rem;
  align-items: center;
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 20px;
  background: rgba(248, 250, 252, 0.86);
}

.hall-custom-bg-panel__upload {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 160px;
  padding: 0.78rem 1rem;
  border-radius: 16px;
  border: 1px dashed rgba(14, 165, 233, 0.42);
  background: rgba(34, 211, 238, 0.08);
  color: #0369a1;
  font-weight: 700;
  cursor: pointer;
}

.hall-mute-toolbar {
  margin-bottom: 0.9rem;
}

.hall-settings__select {
  display: block;
  margin-top: 0.35rem;
  min-width: 9rem;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.86);
  padding: 0.72rem 0.85rem;
}

.hall-mute-members {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 0.75rem;
}

.hall-mute-member,
.hall-mute-card {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem;
  padding: 0.8rem 0.9rem;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  background: rgba(255, 255, 255, 0.82);
}

.hall-mute-member__avatar,
.hall-mute-card__avatar {
  width: 2.8rem;
  height: 2.8rem;
  border-radius: 999px;
  object-fit: cover;
}

.hall-mute-member__avatar--fallback,
.hall-mute-card__avatar--fallback {
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #60a5fa, #2563eb);
  color: #fff;
  font-weight: 800;
}

.hall-mute-member__meta,
.hall-mute-card__meta {
  min-width: 0;
  display: grid;
}

.hall-mute-member__meta strong,
.hall-mute-card__meta strong {
  color: #0f172a;
}

.hall-mute-member__meta small,
.hall-mute-card__meta small,
.hall-mute-list__empty,
.hall-settings__error {
  color: #64748b;
}

.hall-mute-list {
  display: grid;
  gap: 0.7rem;
  margin-top: 1rem;
}

.hall-settings__panel-head--compact {
  margin-top: 1.2rem;
  align-items: flex-start;
}

.hall-command-preview {
  margin-top: 1.2rem;
  border-top: 1px solid rgba(148, 163, 184, 0.16);
  padding-top: 1rem;
}

.hall-command-preview__tabs {
  display: flex;
  gap: 0.45rem;
}

.hall-command-preview__tab {
  border: 1px solid rgba(14, 165, 233, 0.18);
  background: rgba(14, 165, 233, 0.08);
  color: #0369a1;
  border-radius: 999px;
  padding: 0.45rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.hall-command-preview__tab.is-active {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(59, 130, 246, 0.18));
  border-color: rgba(14, 165, 233, 0.28);
}

.hall-command-preview__list {
  display: grid;
  gap: 0.75rem;
}

.hall-command-card {
  display: grid;
  gap: 0.65rem;
  padding: 0.95rem 1rem;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  background: rgba(255, 255, 255, 0.84);
}

.hall-command-card__meta {
  display: grid;
  gap: 0.18rem;
}

.hall-command-card__meta strong {
  color: #0f172a;
}

.hall-command-card__meta small {
  color: #64748b;
}

.hall-command-card__code {
  margin: 0;
  padding: 0.8rem 0.95rem;
  border-radius: 14px;
  background: #0f172a;
  color: #e2e8f0;
  font-size: 0.84rem;
  line-height: 1.55;
  white-space: pre-wrap;
  word-break: break-word;
}

.hall-command-card__copy {
  justify-self: start;
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: rgba(255, 255, 255, 0.92);
  color: #0f172a;
  border-radius: 999px;
  padding: 0.48rem 0.95rem;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.hall-settings-enter-active,
.hall-settings-leave-active {
  transition: opacity 220ms ease;
}

.hall-settings-enter-active .hall-settings-dialog,
.hall-settings-leave-active .hall-settings-dialog {
  transition: transform 260ms ease, opacity 260ms ease;
}

.hall-settings-enter-from,
.hall-settings-leave-to {
  opacity: 0;
}

.hall-settings-enter-from .hall-settings-dialog,
.hall-settings-leave-to .hall-settings-dialog {
  opacity: 0;
  transform: translateY(16px) scale(0.98);
}

.hall-typing-indicator {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  opacity: 0;
  visibility: hidden;
  transform: translateX(-5px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255, 255, 255, 0.6);
  padding: 0.15rem 0.5rem 0.15rem 0.15rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.2);
}

.hall-typing-indicator.is-active {
  opacity: 1;
  visibility: visible;
  transform: translateX(0);
}

:global(.theme-dark) .hall-typing-indicator {
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(56, 189, 248, 0.15);
}

.hall-typing-avatars {
  display: flex;
  align-items: center;
}

.hall-typing-avatar {
  width: 1.4rem;
  height: 1.4rem;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid white;
  margin-left: -0.4rem;
  background: #cbd5e1;
}

.hall-typing-avatar:first-child {
  margin-left: 0;
}

.hall-typing-avatar--fallback {
  display: grid;
  place-items: center;
  font-size: 0.65rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #38bdf8, #818cf8);
}

:global(.theme-dark) .hall-typing-avatar {
  border-color: #0f172a;
}

.hall-typing-dots {
  display: flex;
  align-items: center;
  gap: 2px;
  padding-left: 0.2rem;
}

.hall-typing-text {
  font-size: 0.76rem;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}

:global(.theme-dark) .hall-typing-text {
  color: #cbd5e1;
}

.hall-typing-dots span {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: #0ea5e9;
  animation: typing-bounce 1.4s infinite ease-in-out both;
}

.hall-typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.hall-typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing-bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}



.hall-topbar__crowd {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.55rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.58);
}

:global(.theme-dark) .hall-topbar__crowd {
  background: rgba(15, 23, 42, 0.72);
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-crowd-avatar {
  position: relative;
  display: inline-flex;
  margin-left: -0.3rem;
}

.hall-crowd-avatar:first-child {
  margin-left: 0;
}

.hall-crowd-avatar__img {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.85);
  object-fit: cover;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
}

:global(.theme-dark) .hall-crowd-avatar__img {
  border-color: rgba(15, 23, 42, 0.88);
}

.hall-crowd-avatar__img--fallback {
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #60a5fa, #2563eb);
  color: #fff;
  font-size: 0.74rem;
  font-weight: 800;
}

.hall-crowd-avatar__img--admin {
  background: linear-gradient(135deg, #f472b6, #8b5cf6);
}

.hall-crowd-avatar__dot {
  position: absolute;
  right: 0.05rem;
  bottom: 0.05rem;
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
  border: 2px solid #fff;
  background: #22c55e;
}

:global(.theme-dark) .hall-crowd-avatar__dot {
  border-color: #0f172a;
}

.hall-crowd-avatar__dot--typing {
  background: #0ea5e9;
}

.hall-crowd-more {
  display: inline-flex;
  align-items: center;
  gap: 0.28rem;
  margin-left: 0.45rem;
  padding: 0.45rem 0.55rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.14);
  border: 1px solid rgba(148, 163, 184, 0.16);
}

:global(.theme-dark) .hall-crowd-more {
  background: rgba(51, 65, 85, 0.72);
  border-color: rgba(148, 163, 184, 0.12);
}

.hall-crowd-more span {
  width: 0.28rem;
  height: 0.28rem;
  border-radius: 999px;
  background: #64748b;
}

:global(.theme-dark) .hall-crowd-more span {
  background: #cbd5e1;
}

.hall-main {
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.hall-feed {
  display: grid;
  grid-template-rows: minmax(0, 1fr) auto;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.hall-feed__scroll {
  position: relative;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
  padding: 1.5rem;
}

.hall-message-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* System event records (bans, recalls) */
.hall-system-event {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  padding: 0.6rem 0;
}

.hall-system-event__pill {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.9rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.12);
  font-size: 0.82rem;
  color: #64748b;
}

.hall-system-event__pill strong {
  color: #3b82f6;
  font-weight: 600;
}

.hall-system-event__pill--ban {
  background: rgba(239, 68, 68, 0.08);
  color: #dc2626;
}

:global(.theme-dark) .hall-system-event__pill {
  background: rgba(56, 189, 248, 0.08);
  color: #94a3b8;
}

:global(.theme-dark) .hall-system-event__pill strong {
  color: #38bdf8;
}

:global(.theme-dark) .hall-system-event__pill--ban {
  background: rgba(239, 68, 68, 0.1);
  color: #fca5a5;
}

.hall-system-event__time {
  font-size: 0.72rem;
  color: #94a3b8;
}

/* Recall button on hover */
.hall-message__recall {
  display: inline-flex;
  align-items: center;
  border: none;
  background: rgba(239, 68, 68, 0.08);
  color: #dc2626;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.72rem;
  cursor: pointer;
  transition: all 0.2s;
  margin-left: 0.4rem;
}

.hall-message__recall:hover {
  background: rgba(239, 68, 68, 0.16);
}

:global(.theme-dark) .hall-message__recall {
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.15s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.hall-message {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 0.9rem;
    align-items: flex-start;
  }

  .hall-message--self {
    grid-template-columns: minmax(0, 1fr) auto;
  }

  .hall-message--self .hall-message__body {
    justify-self: end;
    align-items: flex-end;
  color: #082f49;
  border-bottom-right-radius: 12px;
}

.hall-message--self .hall-bubble__content,
.hall-message--self .hall-message__author,
.hall-message--self .hall-message__time {
  color: #082f49;
}

:global(.theme-dark) .hall-message--self .hall-bubble {
  color: #e0f2fe;
}

:global(.theme-dark) .hall-message--self .hall-bubble__content,
:global(.theme-dark) .hall-message--self .hall-message__author,
:global(.theme-dark) .hall-message--self .hall-message__time {
  color: #e0f2fe;
}

.hall-message--bot .hall-bubble {
  background: linear-gradient(135deg, rgba(34, 211, 238, 0.1), rgba(255, 255, 255, 0.92));
}

:global(.theme-dark) .hall-message--bot .hall-bubble {
  background: linear-gradient(135deg, rgba(34, 211, 238, 0.12), rgba(15, 23, 42, 0.94));
}

.hall-message--pending {
  opacity: 0.6;
  filter: grayscale(0.2);
  transition: opacity 0.3s ease;
}

.hall-message__avatar-link,
.hall-message__self-avatar-wrap {
  display: flex;
  align-items: flex-end;
}

.hall-message__avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 999px;
  object-fit: cover;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.hall-message__avatar--fallback {
  display: grid;
  place-items: center;
  color: #fff;
  font-weight: 800;
}

.hall-message__avatar--user {
  background: linear-gradient(135deg, #60a5fa, #2563eb);
}

.hall-message__avatar--admin {
  background: linear-gradient(135deg, #f472b6, #8b5cf6);
}

.hall-message__avatar--bot {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
}

.hall-message__avatar--self {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
}

.hall-message__body {
  display: flex;
  flex-direction: column;
  min-width: 0;
  max-width: min(80%, 48rem);
}

.hall-message__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem 0.65rem;
  margin-bottom: 0.35rem;
  padding: 0 0.25rem;
}

.hall-message__author {
  font-size: 0.93rem;
  font-weight: 700;
  color: #0f172a;
}

:global(.theme-dark) .hall-message__author {
  color: #f8fafc;
}

.hall-message__role {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  background: rgba(99, 102, 241, 0.12);
  color: #7c3aed;
  padding: 0.18rem 0.48rem;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.hall-message__role--bot {
  background: rgba(14, 165, 233, 0.12);
  color: #0284c7;
}

.hall-message__time {
  font-size: 0.78rem;
  color: #64748b;
}

:global(.theme-dark) .hall-message__time {
  color: #94a3b8;
}

.hall-bubble {
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 26px;
  border-bottom-left-radius: 12px;
  background: rgba(255, 255, 255, 0.88);
  padding: 1rem 1.05rem 0.9rem;
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

:global(.theme-dark) .hall-bubble {
  background: rgba(15, 23, 42, 0.88);
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-bubble__content {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.74;
  font-size: 0.96rem;
  color: #334155;
}

.hall-mention {
  display: inline;
  font-weight: 700;
}

.hall-mention--valid {
  color: #2563eb;
}

:global(.theme-dark) .hall-mention--valid {
  color: #7dd3fc;
}

.hall-mention--invalid {
  color: inherit;
}

:global(.theme-dark) .hall-bubble__content {
  color: #e2e8f0;
}

/* TTS button for Alma messages */
.hall-bubble__tts-wrap {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 0.4rem;
}

.hall-bubble__tts-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.8rem;
  height: 1.8rem;
  border-radius: 999px;
  border: 1px solid rgba(14, 165, 233, 0.25);
  background: rgba(14, 165, 233, 0.08);
  color: #0ea5e9;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.hall-bubble__tts-btn svg {
  width: 0.9rem;
  height: 0.9rem;
}

.hall-bubble__tts-btn:hover {
  background: rgba(14, 165, 233, 0.16);
  border-color: rgba(14, 165, 233, 0.4);
  transform: scale(1.05);
}

.hall-bubble__tts-btn.is-speaking {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
  color: #dc2626;
  animation: tts-pulse 1.5s ease-in-out infinite;
}

.hall-bubble__tts-btn.is-speaking:hover {
  background: rgba(239, 68, 68, 0.16);
  border-color: rgba(239, 68, 68, 0.4);
}

@keyframes tts-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.08); }
}

:global(.theme-dark) .hall-bubble__tts-btn {
  border-color: rgba(56, 189, 248, 0.25);
  background: rgba(56, 189, 248, 0.08);
  color: #38bdf8;
}

:global(.theme-dark) .hall-bubble__tts-btn:hover {
  background: rgba(56, 189, 248, 0.16);
  border-color: rgba(56, 189, 248, 0.4);
}

:global(.theme-dark) .hall-bubble__tts-btn.is-speaking {
  background: rgba(248, 113, 113, 0.1);
  border-color: rgba(248, 113, 113, 0.3);
  color: #f87171;
}

.hall-bubble__attachments {
  display: grid;
  gap: 0.7rem;
  margin-top: 0.8rem;
}

.hall-bubble__tool-receipts {
  display: grid;
  gap: 0.55rem;
  margin-top: 0.8rem;
  padding-top: 0.75rem;
  border-top: 1px dashed rgba(148, 163, 184, 0.22);
}

.hall-bubble__tool-receipts-head {
  font-size: 0.76rem;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.02em;
}

.hall-tool-receipt {
  display: grid;
  gap: 0.32rem;
  padding: 0.65rem 0.8rem;
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.05);
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.hall-tool-receipt.is-error {
  background: rgba(239, 68, 68, 0.06);
  border-color: rgba(239, 68, 68, 0.2);
}

.hall-tool-receipt__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
}

.hall-tool-receipt__top strong {
  color: #0f172a;
  font-size: 0.82rem;
}

.hall-tool-receipt__top span {
  font-size: 0.74rem;
  font-weight: 700;
  color: #0f766e;
}

.hall-tool-receipt.is-error .hall-tool-receipt__top span {
  color: #b91c1c;
}

.hall-tool-receipt__summary {
  font-size: 0.78rem;
  color: #475569;
  line-height: 1.5;
}

/* Model Switch Tool - Special Rendering */
.hall-model-switch {
  display: flex;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(59, 130, 246, 0.08));
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 12px;
  position: relative;
  overflow: hidden;
}

.hall-model-switch::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #8b5cf6, #3b82f6);
}

.hall-model-switch.is-error {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(249, 115, 22, 0.08));
  border-color: rgba(239, 68, 68, 0.25);
}

.hall-model-switch.is-error::before {
  background: linear-gradient(90deg, #ef4444, #f97316);
}

.hall-model-switch__icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(139, 92, 246, 0.15);
  border-radius: 10px;
  color: #8b5cf6;
}

.hall-model-switch__icon svg {
  width: 20px;
  height: 20px;
}

.hall-model-switch__content {
  flex: 1;
  min-width: 0;
}

.hall-model-switch__title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.hall-model-switch__title strong {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
}

.hall-model-switch__status--success {
  font-size: 0.72rem;
  font-weight: 600;
  color: #16a34a;
  background: rgba(22, 163, 74, 0.12);
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.hall-model-switch__status--error {
  font-size: 0.72rem;
  font-weight: 600;
  color: #dc2626;
  background: rgba(220, 38, 38, 0.12);
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.hall-model-switch__config {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-top: 0.5rem;
  padding: 0.5rem 0.65rem;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 8px;
  border: 1px solid rgba(139, 92, 246, 0.12);
}

.hall-model-switch__model {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.78rem;
}

.hall-model-switch__label {
  color: #64748b;
  flex-shrink: 0;
}

.hall-model-switch__value {
  color: #1e293b;
  font-weight: 500;
  font-family: ui-monospace, SFMono-Regular, monospace;
  font-size: 0.75rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hall-model-switch__summary {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.3rem;
}

/* Dark mode for model switch */
:global(.theme-dark) .hall-model-switch {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(59, 130, 246, 0.15));
  border-color: rgba(139, 92, 246, 0.35);
}

:global(.theme-dark) .hall-model-switch__title strong {
  color: #f1f5f9;
}

:global(.theme-dark) .hall-model-switch__icon {
  background: rgba(139, 92, 246, 0.25);
  color: #a78bfa;
}

:global(.theme-dark) .hall-model-switch__label {
  color: #94a3b8;
}

:global(.theme-dark) .hall-model-switch__value {
  color: #e2e8f0;
}

:global(.theme-dark) .hall-model-switch__summary {
  color: #94a3b8;
}

:global(.theme-dark) .hall-model-switch__config {
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(139, 92, 246, 0.2);
}

:global(.theme-dark) .hall-tool-receipt.is-error .hall-tool-receipt__top span {
  color: #fca5a5;
}

:global(.theme-dark) .hall-tool-receipt {
  background: rgba(2, 6, 23, 0.32);
  border-color: rgba(56, 189, 248, 0.12);
}

:global(.theme-dark) .hall-tool-receipt.is-error {
  background: rgba(127, 29, 29, 0.22);
  border-color: rgba(248, 113, 113, 0.22);
}

:global(.theme-dark) .hall-tool-receipt__top strong {
  color: #e2e8f0;
}

:global(.theme-dark) .hall-tool-receipt__top span {
  color: #5eead4;
}

:global(.theme-dark) .hall-tool-receipt.is-error .hall-tool-receipt__top span {
  color: #fca5a5;
}

:global(.theme-dark) .hall-tool-receipt__summary,
:global(.theme-dark) .hall-bubble__tool-receipts-head {
  color: #94a3b8;
}

.hall-attachment {
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.56);
  padding: 0.8rem;
}

:global(.theme-dark) .hall-attachment {
  background: rgba(2, 6, 23, 0.36);
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-attachment__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.8rem;
}

.hall-attachment__open {
  font-size: 0.76rem;
  color: #0ea5e9;
}

.hall-attachment__image {
  width: 100%;
  max-height: 18rem;
  margin-top: 0.8rem;
  border-radius: 14px;
  object-fit: cover;
}

.hall-attachment__text {
  margin: 0.7rem 0 0;
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.06);
  padding: 0.7rem 0.8rem;
  white-space: pre-wrap;
  line-height: 1.65;
  font-size: 0.82rem;
}

:global(.theme-dark) .hall-attachment__text {
  background: rgba(15, 23, 42, 0.66);
}

.hall-bubble__status {
  margin-top: 0.65rem;
  font-size: 0.76rem;
  color: rgba(8, 47, 73, 0.8);
}

:global(.theme-dark) .hall-bubble__status {
  color: rgba(224, 242, 254, 0.82);
}

.hall-bubble__status--error {
  color: #dc2626;
}

:global(.theme-dark) .hall-bubble__status--error {
  color: #fecaca;
}

.hall-state {
  display: grid;
  place-items: center;
  min-height: 10rem;
  border-radius: 24px;
  border: 1px dashed rgba(148, 163, 184, 0.18);
  color: #64748b;
  background: rgba(255, 255, 255, 0.45);
  text-align: center;
  padding: 1rem;
}

:global(.theme-dark) .hall-state {
  background: rgba(2, 6, 23, 0.26);
  color: #94a3b8;
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-state--error {
  border-style: solid;
  color: #dc2626;
  background: rgba(254, 242, 242, 0.95);
}

:global(.theme-dark) .hall-state--error {
  color: #fecaca;
  background: rgba(127, 29, 29, 0.24);
}

.hall-state--inline {
  min-height: auto;
  margin-bottom: 0.8rem;
  padding: 0.9rem 1rem;
}

.hall-state__stack {
  display: grid;
  gap: 0.9rem;
  justify-items: center;
}

.hall-state__retry {
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
  color: #082f49;
  padding: 0.8rem 1.25rem;
  font-weight: 800;
}

.hall-composer-wrap {
  flex-shrink: 0;
  padding: 1rem 1.25rem 1.25rem;
  border-top: 1px solid rgba(148, 163, 184, 0.14);
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

:global(.theme-dark) .hall-composer-wrap {
  background: rgba(2, 6, 23, 0.72);
  border-top-color: rgba(56, 189, 248, 0.12);
}

.hall-composer__files {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-bottom: 0.8rem;
}

.hall-file-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  max-width: 16rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.6);
  padding: 0.4rem 0.85rem;
  font-size: 0.78rem;
  color: #0f172a;
}

:global(.theme-dark) .hall-file-chip {
  background: rgba(15, 23, 42, 0.76);
  color: #e2e8f0;
  border-color: rgba(56, 189, 248, 0.14);
}

.hall-file-chip__remove {
  color: #64748b;
}

.hall-composer-shell {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 0.9rem;
  align-items: end;
}

.hall-composer__attach {
  display: grid;
  place-items: center;
  width: 3.1rem;
  height: 3.1rem;
  border-radius: 50%;
  border: 1px solid rgba(34, 211, 238, 0.24);
  background: linear-gradient(135deg, rgba(34, 211, 238, 0.12), rgba(14, 165, 233, 0.14));
  color: #0ea5e9;
  font-size: 1.3rem;
  font-weight: 800;
  cursor: pointer;
  flex-shrink: 0;
}

.hall-composer-card {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  gap: 0.8rem;
  padding: 0.6rem;
  border-radius: 28px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.86);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

:global(.theme-dark) .hall-composer-card {
  background: rgba(15, 23, 42, 0.92);
  border-color: rgba(56, 189, 248, 0.14);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.hall-composer__editor {
  position: relative;
  min-width: 0;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.68);
  overflow: visible;
}

:global(.theme-dark) .hall-composer__editor {
  background: rgba(15, 23, 42, 0.38);
}

.hall-composer__mirror,
.hall-composer__input {
  width: 100%;
  min-height: 3.2rem;
  max-height: 10rem;
  padding: 0.52rem 0.4rem 0.52rem 0.45rem;
  line-height: 1.72;
  font-size: 0.98rem;
  font-family: var(--font-body);
  white-space: pre-wrap;
  word-break: break-word;
  background: transparent !important;
}

.hall-composer__mirror {
  pointer-events: none;
  color: #0f172a;
}

:global(.theme-dark) .hall-composer__mirror {
  color: #f8fafc;
}

.hall-composer__mirror-tail {
  display: inline-block;
  width: 1px;
}

.hall-composer__placeholder {
  color: #64748b;
}

:global(.theme-dark) .hall-composer__placeholder {
  color: #94a3b8;
}

.hall-composer__input {
  position: absolute;
  inset: 0;
  resize: none;
  border: 0;
  background: transparent !important;
  color: transparent !important;
  -webkit-text-fill-color: transparent;
  caret-color: #0f172a;
  outline: none;
  box-shadow: none;
}

:global(.theme-dark) .hall-composer__input {
  caret-color: #f8fafc;
}

.hall-mention-panel {
  position: absolute;
  left: 0;
  bottom: calc(100% + 0.75rem);
  z-index: 20;
  width: min(24rem, 100%);
  display: grid;
  gap: 0.35rem;
  max-height: min(22rem, 60vh);
  overflow-y: auto;
  padding: 0.55rem;
  border-radius: 22px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

:global(.theme-dark) .hall-mention-panel {
  background: rgba(15, 23, 42, 0.96);
  border-color: rgba(56, 189, 248, 0.16);
}

.hall-mention-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.35rem 0.45rem 0.6rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.14);
}

:global(.theme-dark) .hall-mention-panel__header {
  border-bottom-color: rgba(56, 189, 248, 0.12);
}

.hall-mention-panel__title {
  color: #0f172a;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.02em;
}

:global(.theme-dark) .hall-mention-panel__title {
  color: #e2e8f0;
}

.hall-mention-panel__hint {
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  background: rgba(14, 165, 233, 0.1);
  color: #0369a1;
  font-size: 0.72rem;
  font-weight: 700;
}

:global(.theme-dark) .hall-mention-panel__hint {
  background: rgba(34, 211, 238, 0.14);
  color: #67e8f9;
}

.hall-mention-panel__item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 0.7rem;
  align-items: center;
  width: 100%;
  padding: 0.72rem 0.78rem;
  border-radius: 15px;
  border: 1px solid transparent;
  color: #0f172a;
  text-align: left;
  transition: background-color 160ms ease, transform 160ms ease, border-color 160ms ease;
}

:global(.theme-dark) .hall-mention-panel__item {
  color: #f8fafc;
}

.hall-mention-panel__item--active,
.hall-mention-panel__item:hover {
  background: rgba(37, 99, 235, 0.08);
  border-color: rgba(37, 99, 235, 0.14);
  transform: translateY(-1px);
}

:global(.theme-dark) .hall-mention-panel__item--active,
:global(.theme-dark) .hall-mention-panel__item:hover {
  background: rgba(14, 165, 233, 0.14);
  border-color: rgba(34, 211, 238, 0.2);
}

.hall-mention-panel__avatar {
  display: grid;
  place-items: center;
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 999px;
  overflow: hidden;
  background: linear-gradient(135deg, #60a5fa, #2563eb);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 800;
}

.hall-mention-panel__avatar--image {
  display: block;
  object-fit: cover;
  background: #e2e8f0;
}

.hall-mention-panel__avatar--admin {
  background: linear-gradient(135deg, #f472b6, #8b5cf6);
}

.hall-mention-panel__avatar--bot {
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
}

.hall-mention-panel__meta {
  min-width: 0;
  display: grid;
  gap: 0.15rem;
}

.hall-mention-panel__name-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.hall-mention-panel__name {
  color: #2563eb;
  font-weight: 700;
}

:global(.theme-dark) .hall-mention-panel__name {
  color: #7dd3fc;
}

.hall-mention-panel__tag {
  flex-shrink: 0;
  padding: 0.14rem 0.42rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.14);
  color: #475569;
  font-size: 0.64rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

:global(.theme-dark) .hall-mention-panel__tag {
  background: rgba(148, 163, 184, 0.12);
  color: #cbd5e1;
}

.hall-mention-panel__status {
  color: #64748b;
  font-size: 0.76rem;
}

:global(.theme-dark) .hall-mention-panel__status {
  color: #94a3b8;
}

.hall-composer__send {
  height: 3.25rem;
  width: 3.25rem;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #22d3ee, #0ea5e9);
  color: #082f49;
  display: grid;
  place-items: center;
  font-weight: 800;
  box-shadow: 0 12px 28px rgba(34, 211, 238, 0.24);
}

.hall-composer__send svg {
  width: 1.25rem;
  height: 1.25rem;
}

.hall-composer__send:disabled {
  opacity: 0.65;
}

@media (max-width: 768px) {
  .hall-topbar {
    padding: 0.9rem;
    flex-direction: column;
    align-items: stretch;
  }

  .hall-topbar__main {
    justify-content: space-between;
  }

  .hall-topbar__side {
    justify-content: space-between;
  }

  .hall-topbar__tabs {
    flex-wrap: wrap;
  }

  .hall-feed__scroll,
  .hall-composer-wrap {
    padding-left: 0.9rem;
    padding-right: 0.9rem;
  }

  .hall-feed__scroll {
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .hall-message__body {
    max-width: 88%;
  }

  .hall-composer-card {
    grid-template-columns: minmax(0, 1fr);
  }

  .hall-composer__send {
    width: 100%;
    border-radius: 18px;
  }

  .hall-topbar__crowd {
    flex: 1;
    min-width: 0;
    overflow-x: auto;
  }

  .hall-theme-grid,
  .hall-custom-bg-panel {
    grid-template-columns: 1fr;
  }

  .hall-settings__header,
  .hall-settings__footer {
    flex-direction: column;
    align-items: stretch;
  }
}

/* Bot 头像右键菜单 */
:global(.hall-ctx-menu) {
  position: fixed;
  z-index: 9999;
  background: var(--hall-bubble-bg, #fff);
  border: 1px solid rgba(0,0,0,.12);
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,.15);
  padding: 4px 0;
  min-width: 160px;
  overflow: hidden;
}
:global(.hall-ctx-menu__item) {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 14px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: .8rem;
  color: var(--hall-text, #374151);
  text-align: left;
  transition: background .12s;
}
:global(.hall-ctx-menu__item:hover) {
  background: rgba(99,102,241,.1);
  color: #6366f1;
}

/* 加载动画 */
.hall-loading-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  z-index: 10;
  pointer-events: none;
}

.hall-loading-spinner {
  position: relative;
  width: 64px;
  height: 64px;
}

.hall-loading-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 3px solid transparent;
  border-top-color: #818cf8;
  animation: spin 1.1s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite;
}

.hall-loading-ring--2 {
  inset: 6px;
  border-top-color: #c084fc;
  animation-delay: -0.4s;
  animation-duration: 1.4s;
}

.hall-loading-ring--3 {
  inset: 12px;
  border-top-color: #f472b6;
  animation-delay: -0.8s;
  animation-duration: 1.8s;
}

.hall-loading-dot {
  position: absolute;
  inset: 20px;
  background: #818cf8;
  border-radius: 50%;
  animation: pulse 1.5s ease-in-out infinite;
  box-shadow: 0 0 12px #818cf8;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { transform: scale(0.7); opacity: 0.6; }
  50% { transform: scale(1); opacity: 1; }
}

.hall-loading-text {
  font-size: 0.875rem;
  color: #94a3b8;
  letter-spacing: 0.05em;
  animation: textPulse 2s ease-in-out infinite;
}

@keyframes textPulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}

/* 思考动画 */
.hall-thinking {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 6px;
  padding: 4px 8px;
  background: rgba(99, 102, 241, 0.08);
  border-radius: 6px;
  width: fit-content;
}

.hall-thinking__dot {
  width: 5px;
  height: 5px;
  background: #818cf8;
  border-radius: 50%;
  animation: thinkingBounce 1.4s ease-in-out infinite;
}

.hall-thinking__dot:nth-child(2) { animation-delay: 0.2s; }
.hall-thinking__dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes thinkingBounce {
  0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
  40% { transform: translateY(-4px); opacity: 1; }
}

.hall-thinking__text {
  font-size: 0.75rem;
  color: #818cf8;
  margin-left: 4px;
  animation: textFade 1.5s ease-in-out infinite;
}

@keyframes textFade {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

/* 工具调用可视化 */
.hall-tool-viz {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 8px;
  padding: 8px;
  background: rgba(99, 102, 241, 0.06);
  border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 10px;
}

.hall-tool-viz__item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.hall-tool-viz__header {
  display: flex;
  align-items: center;
  gap: 6px;
}

.hall-tool-viz__icon {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #818cf8;
  flex-shrink: 0;
}

.hall-tool-viz__icon svg {
  width: 14px;
  height: 14px;
}

.hall-tool-viz__spinner {
  animation: spin 0.8s linear infinite;
  transform-origin: center;
}

.hall-tool-viz__item--done .hall-tool-viz__icon {
  color: #22c55e;
}

.hall-tool-viz__item--error .hall-tool-viz__icon {
  color: #ef4444;
}

.hall-tool-viz__name {
  font-size: 0.8rem;
  font-weight: 600;
  color: #6366f1;
}

.hall-tool-viz__status {
  font-size: 0.7rem;
  color: #94a3b8;
  margin-left: auto;
}

.hall-tool-viz__status--done {
  color: #22c55e;
}

.hall-tool-viz__status--error {
  color: #ef4444;
}

.hall-tool-viz__result {
  margin-left: 22px;
  padding: 4px 8px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 6px;
  border-left: 2px solid #818cf8;
}

.hall-tool-viz__result-preview {
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.4;
}

/* 消息列表渐入动画 */
.hall-message-list {
  animation: listFadeIn 0.4s ease-out;
}

@keyframes listFadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 单条消息渐入 */
.hall-message {
  animation: msgSlideIn 0.3s ease-out;
}

@keyframes msgSlideIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<!-- FIXME COPY FROM apps/files/src/components/TemplatePreview.vue should be deduplicated -->
<template>
	<li class="template-picker__item">
		<input :id="id"
			:checked="checked"
			type="radio"
			class="radio"
			name="template-picker"
			@change="onCheck">

		<label :for="id" class="template-picker__label">
			<div class="template-picker__preview"
				:class="failedPreview ? 'template-picker__preview--failed' : ''">
				<img class="template-picker__image"
					:src="realPreviewUrl"
					alt=""
					draggable="false"
					@error="onFailure">
			</div>

			<span class="template-picker__title">
				{{ nameWithoutExt }}
			</span>
		</label>
	</li>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

const encodeFilePath = function(path) {
	const pathSections = (path.startsWith('/') ? path : `/${path}`).split('/')
	let relativePath = ''
	pathSections.forEach((section) => {
		if (section !== '') {
			relativePath += '/' + encodeURIComponent(section)
		}
	})
	return relativePath
}

const isPublic = function() {
	return !getCurrentUser()
}

const getToken = function() {
	return document.getElementById('sharingToken') && document.getElementById('sharingToken').value
}

// preview width generation
const previewWidth = 256

export default {
	name: 'NewMessageTemplatePreview',
	inheritAttrs: false,

	props: {
		basename: {
			type: String,
			required: true,
		},

		checked: {
			type: Boolean,
			default: false,
		},

		fileid: {
			type: [String, Number],
			required: true,
		},

		filename: {
			type: String,
			required: true,
		},

		previewUrl: {
			type: String,
			default: null,
		},

		hasPreview: {
			type: Boolean,
			default: true,
		},

		mime: {
			type: String,
			required: true,
		},

		ratio: {
			type: Number,
			default: null,
		},
	},

	emits: ['check'],

	data() {
		return {
			failedPreview: false,
		}
	},

	computed: {
		/**
		 * Strip away extension from name
		 *
		 * @return {string}
		 */
		nameWithoutExt() {
			return this.basename.includes('.') ? this.basename.split('.').slice(0, -1).join('.') : this.basename
		},

		id() {
			return `template-picker-${this.fileid}`
		},

		realPreviewUrl() {
			// If original preview failed, fallback to mime icon
			if (this.failedPreview && this.mimeIcon) {
				return this.mimeIcon
			}

			if (this.previewUrl) {
				return this.previewUrl
			}
			// TODO: find a nicer standard way of doing this?
			if (isPublic()) {
				return generateUrl(`/apps/files_sharing/publicpreview/${getToken()}?fileId=${this.fileid}&file=${encodeFilePath(this.filename)}&x=${previewWidth}&y=${previewWidth}&a=1`)
			}
			return generateUrl(`/core/preview?fileId=${this.fileid}&x=${previewWidth}&y=${previewWidth}&a=1`)
		},

		mimeIcon() {
			return OC.MimeType.getIconUrl(this.mime)
		},
	},

	methods: {
		onCheck() {
			this.$emit('check', this.fileid)
		},

		onFailure() {
			this.failedPreview = true
		},
	},
}
</script>

<style lang="scss" scoped>

.template-picker {
	&__item {
		display: flex;
	}

	&__label {
		display: flex;
		// Align in the middle of the grid
		align-items: center;
		flex: 1 1;
		flex-direction: column;

		&, * {
			cursor: pointer;
			user-select: none;
		}

		&::before {
			display: none !important;
		}
	}

	&__preview {
		display: block;
		overflow: hidden;
		// Stretch so all entries are the same width
		flex: 1 1;
		width: var(--width);
		min-height: var(--height);
		max-height: var(--height);
		padding: 0;
		border: var(--border) solid var(--color-border);
		border-radius: var(--border-radius-large);

		input:checked + label > & {
			border-color: var(--color-primary-element);
		}

		&--failed {
			// Make sure to properly center fallback icon
			display: flex;
		}
	}

	&__image {
		max-width: 100%;
		background-color: var(--color-main-background);

		object-fit: cover;
	}

	// Failed preview, fallback to mime icon
	&__preview--failed &__image {
		width: calc(var(--margin) * 8);
		// Center mime icon
		margin: auto;
		background-color: transparent !important;

		object-fit: initial;
	}

	&__title {
		overflow: hidden;
		// also count preview border
		max-width: calc(var(--width) + 2 * 2px);
		padding: var(--margin);
		white-space: nowrap;
		text-overflow: ellipsis;
	}
}

</style>

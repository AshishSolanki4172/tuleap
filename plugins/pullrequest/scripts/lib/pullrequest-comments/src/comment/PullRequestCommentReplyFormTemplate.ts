/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { PullRequestCommentComponentType } from "./PullRequestComment";
import type { GettextProvider } from "@tuleap/gettext";
import { FOCUSABLE_TEXTAREA_CLASSNAME } from "../helpers/textarea-focus-helper";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";

type MapOfClasses = Record<string, boolean>;

export const getReplyFormTemplate = (
    host: PullRequestCommentComponentType,
    gettext_provider: GettextProvider
): UpdateFunction<PullRequestCommentComponentType> => {
    if (!host.reply_comment_presenter) {
        return html``;
    }

    const onClickHideReplyForm = (host: PullRequestCommentComponentType): void => {
        host.controller.hideReplyForm(host);
    };

    const onClickSaveReply = (host: PullRequestCommentComponentType): void => {
        host.controller.saveReply(host);
    };

    const onTextAreaInput = (host: PullRequestCommentComponentType, event: InputEvent): void => {
        const textarea = event.target;
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }

        host.controller.updateCurrentReply(host, textarea.value);
    };

    const getCommentContentClasses = (host: PullRequestCommentComponentType): MapOfClasses => {
        const classes: MapOfClasses = {
            "pull-request-comment-content": true,
        };

        if (host.comment.color) {
            classes[`pull-request-comment-content-color`] = true;
            classes[`tlp-swatch-${host.comment.color}`] = true;
        }

        return classes;
    };

    return html`
        <div class="pull-request-comment-reply-form" data-test="pull-request-comment-reply-form">
            <div class="pull-request-comment pull-request-comment-follow-up-content">
                ${getCommentAvatarTemplate(host.comment.user)}
                <div class="${getCommentContentClasses(host)}">
                    <textarea
                        class="${FOCUSABLE_TEXTAREA_CLASSNAME} tlp-textarea"
                        rows="10"
                        placeholder="${gettext_provider.gettext("Say something…")}"
                        oninput="${onTextAreaInput}"
                        data-test="reply-text-area"
                    ></textarea>
                    <div
                        class="pull-request-comment-footer"
                        data-test="pull-request-comment-footer"
                    >
                        <button
                            type="button"
                            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
                            onclick="${onClickHideReplyForm}"
                            data-test="button-cancel-reply"
                            disabled="${host.reply_comment_presenter.is_being_submitted}"
                        >
                            ${gettext_provider.gettext("Cancel")}
                        </button>
                        <button
                            type="button"
                            class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary"
                            onclick="${onClickSaveReply}"
                            data-test="button-save-reply"
                            disabled="${!host.reply_comment_presenter.is_submittable ||
                            host.reply_comment_presenter.is_being_submitted}"
                        >
                            ${gettext_provider.gettext("Reply")}
                            ${host.reply_comment_presenter.is_being_submitted &&
                            html`
                                <i
                                    class="fa-solid fa-circle-notch fa-spin tlp-button-icon-right"
                                    aria-hidden="true"
                                    data-test="reply-being-saved-spinner"
                                ></i>
                            `}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
};

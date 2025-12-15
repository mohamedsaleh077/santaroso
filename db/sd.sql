CREATE TABLE IF NOT EXISTS boards
(
    id          INT(11) AUTO_INCREMENT NOT NULL,
    name        VARCHAR(255)           NOT NULL,
    description TEXT     DEFAULT NULL,

    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS threads
(
    id         INT(11) AUTO_INCREMENT NOT NULL,
    user_name  VARCHAR(255)           NOT NULL,
    board_id   INT(11)                NOT NULL,

    title      VARCHAR(255)           NULL,
    -- I mistaken in something, so... when I used vibe coding in this part, Ai try to deal with it while it shouldnt exists
    -- so this is here for some stupid reasons blah blah blah

    body       TEXT         DEFAULT NULL,

    media      VARCHAR(255) DEFAULT NULL,

    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (board_id) REFERENCES boards (id) ON DELETE CASCADE,

    INDEX idx_posts_content (title)
);

CREATE TABLE IF NOT EXISTS comments
(
    id         INT(11) AUTO_INCREMENT,

    user_name  VARCHAR(255) NOT NULL,
    thread_id  INT(11)      NOT NULL,

    body       TEXT         NOT NULL,
    media      VARCHAR(255) DEFAULT NULL,

    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins
(
    id        INT(11) AUTO_INCREMENT,
    user_name VARCHAR(255) NOT NULL,
    password  VARCHAR(255) NOT NULL,

    PRIMARY KEY (id)
);
INSERT INTO admins (user_name, password)
VALUES ('admin', 'admin');

CREATE TABLE IF NOT EXISTS reports
(
    id           INT(11) AUTO_INCREMENT     NOT NULL,
    ref_id       INT(11)                    NOT NULL,
    item_type_id ENUM ('thread', 'comment') NOT NULL,

    title        VARCHAR(255)               NOT NULL,
    body         TEXT         DEFAULT NULL,
    media        VARCHAR(255) DEFAULT NULL,

    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_reports_content (title)
);

CREATE TABLE IF NOT EXISTS users_ips_actions
(
    id           INT(11) AUTO_INCREMENT     NOT NULL,
    ref_id       INT(11)                    NOT NULL,
    item_type_id ENUM ('thread', 'comment') NOT NULL,
    ip VARCHAR(255) NOT NULL,
    ban BOOLEAN NOT NULL DEFAULT FALSE,

    PRIMARY KEY (id)
);
-- INSERT INTO boards (name, description) VALUES ('test', 'test board');
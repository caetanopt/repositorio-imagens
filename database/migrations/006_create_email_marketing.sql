-- Email Marketing SaaS tables
-- PostgreSQL / Supabase compatible

-- Email Lists
CREATE TABLE IF NOT EXISTS email_lists (
    id          BIGSERIAL    PRIMARY KEY,
    brand_id    INTEGER      NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
    name        TEXT         NOT NULL,
    description TEXT,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_email_lists_brand_id ON email_lists (brand_id);

DROP TRIGGER IF EXISTS trg_email_lists_updated_at ON email_lists;
CREATE TRIGGER trg_email_lists_updated_at
    BEFORE UPDATE ON email_lists
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Email Contacts
CREATE TABLE IF NOT EXISTS email_contacts (
    id            BIGSERIAL    PRIMARY KEY,
    email         TEXT         NOT NULL,
    name          TEXT,
    brand_id      INTEGER      NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
    status        TEXT         NOT NULL DEFAULT 'active'
                      CHECK (status IN ('active', 'unsubscribed', 'bounced')),
    custom_fields JSONB        NOT NULL DEFAULT '{}',
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    UNIQUE (email, brand_id)
);

CREATE INDEX IF NOT EXISTS idx_email_contacts_brand_id ON email_contacts (brand_id);
CREATE INDEX IF NOT EXISTS idx_email_contacts_email    ON email_contacts (email);
CREATE INDEX IF NOT EXISTS idx_email_contacts_status   ON email_contacts (status);

DROP TRIGGER IF EXISTS trg_email_contacts_updated_at ON email_contacts;
CREATE TRIGGER trg_email_contacts_updated_at
    BEFORE UPDATE ON email_contacts
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Email List Contacts (pivot)
CREATE TABLE IF NOT EXISTS email_list_contacts (
    list_id       BIGINT      NOT NULL REFERENCES email_lists(id) ON DELETE CASCADE,
    contact_id    BIGINT      NOT NULL REFERENCES email_contacts(id) ON DELETE CASCADE,
    subscribed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (list_id, contact_id)
);

CREATE INDEX IF NOT EXISTS idx_email_list_contacts_contact_id ON email_list_contacts (contact_id);

-- Email Campaigns
CREATE TABLE IF NOT EXISTS email_campaigns (
    id           BIGSERIAL    PRIMARY KEY,
    brand_id     INTEGER      NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
    list_id      BIGINT       REFERENCES email_lists(id) ON DELETE SET NULL,
    name         TEXT         NOT NULL,
    subject      TEXT         NOT NULL DEFAULT '',
    from_name    TEXT         NOT NULL DEFAULT '',
    from_email   TEXT         NOT NULL DEFAULT '',
    reply_to     TEXT,
    html_content TEXT,
    text_content TEXT,
    status       TEXT         NOT NULL DEFAULT 'draft'
                     CHECK (status IN ('draft','scheduled','sending','sent','paused')),
    scheduled_at TIMESTAMPTZ,
    sent_at      TIMESTAMPTZ,
    stats        JSONB        NOT NULL DEFAULT '{"sent":0,"delivered":0,"opens":0,"clicks":0,"bounces":0,"unsubscribes":0}',
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_email_campaigns_brand_id ON email_campaigns (brand_id);
CREATE INDEX IF NOT EXISTS idx_email_campaigns_status   ON email_campaigns (status);
CREATE INDEX IF NOT EXISTS idx_email_campaigns_list_id  ON email_campaigns (list_id);

DROP TRIGGER IF EXISTS trg_email_campaigns_updated_at ON email_campaigns;
CREATE TRIGGER trg_email_campaigns_updated_at
    BEFORE UPDATE ON email_campaigns
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Email Suppression list
CREATE TABLE IF NOT EXISTS email_suppression (
    id         BIGSERIAL    PRIMARY KEY,
    email      TEXT         NOT NULL,
    reason     TEXT,
    brand_id   INTEGER      REFERENCES brands(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    UNIQUE (email, brand_id)
);

CREATE INDEX IF NOT EXISTS idx_email_suppression_email    ON email_suppression (email);
CREATE INDEX IF NOT EXISTS idx_email_suppression_brand_id ON email_suppression (brand_id);

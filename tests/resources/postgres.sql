CREATE TABLE event (
    position BIGSERIAL NOT NULL,
    id UUID NOT NULL,
    name VARCHAR(1023) NOT NULL,
    occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    payload JSONB NOT NULL,
    PRIMARY KEY(position)
);
CREATE INDEX ix_event_name ON event (name);
CREATE INDEX ix_event_occurred_at ON event (occurred_at);
CREATE UNIQUE INDEX uq_event_id ON event (id);
COMMENT ON COLUMN event.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN event.occurred_at IS '(DC2Type:datetimetz_immutable)';

CREATE TABLE pointer (
    name VARCHAR(255) NOT NULL,
    value INT NOT NULL, PRIMARY KEY(name)
);

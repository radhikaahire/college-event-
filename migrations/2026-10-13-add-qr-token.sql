-- Run once on an existing CEMS database before issuing secure QR passes.
ALTER TABLE event_participation
    ADD COLUMN qr_token CHAR(64) DEFAULT NULL,
    ADD UNIQUE KEY uq_event_participation_qr_token (qr_token);

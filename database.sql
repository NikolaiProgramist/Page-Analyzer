CREATE TABLE IF NOT EXISTS urls (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) UNIQUE NOT NULL,
    created_at timestamp NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    url_id bigint NOT NULL REFERENCES urls (id),
    status_code int,
    h1 varchar(50),
    title varchar(50),
    description varchar(255),
    created_at timestamp NOT NULL
);
